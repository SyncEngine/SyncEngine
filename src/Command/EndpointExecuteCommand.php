<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use SyncEngine\Controller\DefaultController;
use SyncEngine\EventDispatcher\Event\ExecuteEvent;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\TaskModel;
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
		$this->addOption( 'errors' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$this->output = $output;

		$endpoint = $input->getArgument( 'endpoint' );

		if ( ! $endpoint ) {
			return parent::execute( $input, $output );
		}

		$model = AutomationModel::get( [ 'endpoint' => $endpoint ] );

		if ( ! $model ) {
			$output->writeln( '<error>Endpoint not found</error>: <info>' . $endpoint . '</info>' );
			return Command::INVALID;
		}

		$context = new ExecuteContext( $this->execute, $model );

		$result = $this->execute->execute( $model, $context );

		$success = $result['success'];

		if ( ! $success && $result['errors'] ) {
			if ( $input->getOption( 'errors' ) ) {
				foreach ( $result['errors'] as $error ) {
					$output->writeln( '<error>' . $error['message'] . '</error>' );
				}
			}
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
			( 'trigger' === $event->getEventName() || ! $event->getExecuteContext()->getTrace()->isFinished() )
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
				$progress->finish( '<comment>Endpoint completed</comment>: <info>' . $endpoint . '</info>' );
				unset( $this->progress );
			break;
			case 'error':
				$progress->finish( '<error>Endpoint failed</error>: <info>' . $endpoint . '</info>' );
				unset( $this->progress );
			break;
			default:
				$info = $callback ? $callback( $event ) : $event->getEventName();
				$progress->setMessage( '<comment>Execute endpoint</comment>: <info>' . $endpoint . '</info> > <info>' . $info . '</info> >' );
				$progress->advance();
		}
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
	#[AsEventListener( event: 'syncengine.execute.step' )]
	#[AsEventListener( event: 'syncengine.execute.task' )]
	public function executeTraceEvent( ExecuteEvent $event ): void
	{
		$this->setProgress( $event, [ $this, 'getContextInfo' ] );
		if ( $task->getType() === RequestTaskType::TYPE ) {
		}
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
