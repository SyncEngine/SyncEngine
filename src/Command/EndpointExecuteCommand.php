<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use SyncEngine\Controller\DefaultController;
use SyncEngine\EventDispatcher\Event\ExecuteEvent;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;

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
	private static ?OutputInterface $_output = null;

	public function __construct( Execute $execute, DefaultController $controller )
	{
		$this->execute = $execute;
		parent::__construct( $controller );
	}

	protected function configure(): void
	{
		$this->addArgument( 'endpoint', InputArgument::OPTIONAL, 'The automation endpoint.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		self::$_output = $output;

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
			foreach ( $result['errors'] as $error ) {
				$output->writeln( '<error>' . $error['message'] . '</error>' );
			}
		} else {
			$output->writeln( '<comment>Endpoint completed</comment>: <info>' . $endpoint . '</info>' );
		}

		self::$_output = null;

		return ( $success ) ? Command::SUCCESS : Command::FAILURE;
	}

	#[AsEventListener( event: 'syncengine.execute.trigger' )]
	#[AsEventListener( event: 'syncengine.execute.start' )]
	#[AsEventListener( event: 'syncengine.execute.stop' )]
	#[AsEventListener( event: 'syncengine.execute.error' )]
	#[AsEventListener( event: 'syncengine.execute.success' )]
	public function executeEvent( ExecuteEvent $event ): void
	{
		self::$_output?->writeln( '<comment>Execute event</comment>: <info>' . $event->getEventName() . '</info>' );
	}
}
