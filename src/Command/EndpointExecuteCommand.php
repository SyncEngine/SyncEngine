<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use SyncEngine\Controller\DefaultController;
use SyncEngine\EventDispatcher\Event\ExecuteEvent;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\DataFormatter;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Task\Type\RequestTaskType;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:endpoint:execute',
	description: 'Execute automation endpoint',
)]
class EndpointExecuteCommand extends EndpointCommand
{
	private Execute $execute;
	private ?OutputInterface $output = null;
	private ?ProgressIndicator $progress = null;

	public function __construct( Execute $execute, DefaultController $controller )
	{
		$this->execute = $execute;
		parent::__construct( $controller );
	}

	protected function configure(): void
	{
		$this->addArgument( 'endpoint', InputArgument::OPTIONAL, 'The automation endpoint.' );
		$this->addOption( 'ignore-state', null, InputOption::VALUE_NONE, 'Run the automation even if it is already running.' );
		$this->addOption( 'request', null, InputOption::VALUE_OPTIONAL, 'The request parameters (URL formatted).', null );
		$this->addOption( 'memory-limit', null, InputOption::VALUE_OPTIONAL, 'Set memory limit (MB) if allowed by server.', null );
		$this->addOption( 'errors' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$this->output = $output;

		$endpoint = $input->getArgument( 'endpoint' );

		if ( ! $endpoint ) {
			return parent::execute( $input, $output );
		}

		/** @var AutomationModel $model */
		$model = AutomationModel::get( [ 'endpoint' => $endpoint ] );

		if ( ! $model ) {
			$output->writeln( '<error>Endpoint not found</error>: <info>' . $endpoint . '</info>' );
			return Command::INVALID;
		}

		if ( $model->isRunning() && ! $input->getOption( 'ignore-state' ) ) {
			$output->writeln( '<error>Endpoint already running</error>: <info>' . $endpoint . '</info>' );
			return Command::INVALID;
		}

		$memoryLimit = $input->getOption( 'memory-limit' );
		if ( $memoryLimit ) {
			ini_set( 'memory_limit', $memoryLimit );
		}

		$context = new ExecuteContext( $this->execute, $model );

		$request = null;
		if ( $input->getOption( 'request' ) ) {
			$request = new Request();
			foreach ( ( new DataFormatter() )->decodeFormat( 'url', $request ) as $key => $value ) {
				$request->request->set( $key, $value );
			}
		}

		$result = $this->execute->execute( $model, $context, $request );

		if ( isset( $this->progress ) ) {
			$this->progress->finish( '<comment>Endpoint stopped</comment>: <info>' . $endpoint . '</info> > ' . date( 'Y-m-d H:i:s' ) );
		}

		$success = $result['success'];

		if ( ! $success && $result['errors'] ) {
			if ( $input->getOption( 'errors' ) ) {
				$output->writeln( '' );
				foreach ( $result['errors'] as $error ) {
					$output->writeln( '<error>' . (string) $error . '</error>' );
				}
			}
		}

		if ( ! empty( $result['message'] ) ) {
			$output->writeln( $result['message'] );
		}

		$this->output = null;

		return ( $success ) ? Command::SUCCESS : Command::FAILURE;
	}

	public function getProgress( ExecuteEvent $event ): ?ProgressIndicator
	{
		if ( empty( $this->output ) ) {
			return null;
		}

		$endpoint = $event->getExecuteContext()->getAutomation()->getEndpoint();

		if (
			! isset( $this->progress ) &&
			( 'trigger' === $event->getEventName() || ! $event->getExecuteContext()->getTrace()?->isFinished() )
		) {
			$this->progress = new ProgressIndicator( $this->output, 'very_verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇'] );
			$this->progress->start( '<comment>Executing endpoint</comment>: <info>' . $endpoint . '</info>' );
		}

		return $this->progress ?? null;
	}

	public function setProgress( ExecuteEvent $event, ?callable $callback = null ): void
	{
		$progress = $this->getProgress( $event );
		if ( empty( $progress ) ) {
			return;
		}

		$endpoint = $event->getExecuteContext()->getAutomation()->getEndpoint();
		switch ( $event->getEventName() ) {
			case 'stop':
				//$progress->setMessage( '<comment>Endpoint stopped</comment>: <info>' . $endpoint . '</info>' );
				unset( $this->progress );
			break;
			case 'success':
				$progress->finish( '<comment>Endpoint completed</comment>: <info>' . $endpoint . '</info> > ' . date( 'Y-m-d H:i:s' ) );
				unset( $this->progress );
			break;
			case 'error':
				$progress->finish( '<error>Endpoint failed</error>: <info>' . $endpoint . '</info> > ' . date( 'Y-m-d H:i:s' ) );
				unset( $this->progress );
			break;
			default:
				$info = $callback ? $callback( $event ) : $event->getEventName();
				$progress->setMessage( '<comment>Execute endpoint</comment>: <info>' . $endpoint . '</info> > <info>' . $info . '</info> > ' . date( 'Y-m-d H:i:s' ) );
				$progress->advance();
		}
	}

	public function updateProgress( ExecuteEvent $event, ?callable $callback = null ): void
	{
		static $timer = 0;

		$time = time(); //round( microtime( true ), 1 );
		if ( $timer >= $time ) {
			// Prevent too many updates.
			return;
		}
		$timer = $time;

		$this->setProgress( $event, $callback  );
	}

	#[AsEventListener( event: 'syncengine.execute.trigger' )]
	#[AsEventListener( event: 'syncengine.execute.start' )]
	#[AsEventListener( event: 'syncengine.execute.stop' )]
	#[AsEventListener( event: 'syncengine.execute.error' )]
	#[AsEventListener( event: 'syncengine.execute.success' )]
	public function executeEvent( ExecuteEvent $event ): void
	{
		$this->setProgress( $event );
	}

	#[AsEventListener( event: 'syncengine.execute.flow' )]
	public function executeTraceFlowEvent( ExecuteEvent $event ): void
	{
		$this->setProgress( $event, [ $this, 'getContextInfo' ] );
	}

	#[AsEventListener( event: 'syncengine.execute.step' )]
	public function executeTraceStepEvent( ExecuteEvent $event ): void
	{
		$this->updateProgress( $event, [ $this, 'getContextInfo' ] );
	}

	// @todo Find memory leak.
	//#[AsEventListener( event: 'syncengine.execute.task' )]
	public function executeTraceTaskEvent( ExecuteEvent $event ): void
	{
		$task = $event->getExecuteContext()->getCurrentTask();
		if ( $task->getType() === RequestTaskType::TYPE ) {
			// Always set request type since these take long.
			$this->setProgress( $event, [ $this, 'getContextInfo' ] );
		}

		$this->updateProgress( $event, [ $this, 'getContextInfo' ]  );
	}

	public function getContextInfo( ExecuteEvent $event ): string
	{
		$name = $event->getEventName();
		/** @var EngineModel|ServiceModel $model */
		$model = $event->getExecuteContext()->getCurrent( $name );

		if ( $model instanceof TaskModel ) {
			$name = $name . ':' . $model->getClassLocator();

			if ( ! empty( $model->getConfig('_label') ) ) {
				$name .= ' "' . $model->getConfig('_label') . '"';
			}
		} elseif ( $model instanceof EngineModel ) {
			$name = $name . ' "' . $model->getName() . '"';
		}

		return $name;
	}
}
