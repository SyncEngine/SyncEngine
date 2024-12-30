<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:endpoint:continue',
	description: 'Continue with automation endpoint using an existing trace',
)]
class EndpointContinueCommand extends EndpointCommand
{
	private Execute $execute;

	public function __construct( Execute $execute, DefaultController $controller )
	{
		$this->execute = $execute;
		parent::__construct( $controller );
	}

	protected function configure(): void
	{
		$this->addArgument( 'endpoint', InputArgument::REQUIRED, 'The automation endpoint.' );
		$this->addArgument( 'trace', InputArgument::REQUIRED, 'The trace ID to continue with.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
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

		if ( $model->isRunning() ) {
			$output->writeln( '<error>Endpoint already running</error>: <info>' . $endpoint . '</info>' );
			return Command::INVALID;
		}

		$trace = $input->getArgument( 'trace' );
		$trace = TraceModel::get( $trace );
		if ( ! $trace ) {
			$output->writeln( '<error>Trace not found.</error>' );
			return Command::INVALID;
		}

		if ( $trace->getAutomation()->getId() !== $model->getId() ) {
			$output->writeln( '<error>Trace does not match endpoint.</error>' );
			return Command::INVALID;
		}

		$last_iteration = count( $trace->getTraceFiles() ?? [] );
		$current_iteration = $last_iteration - 1; // The execute handler will add 1 again.

		$model->setIteration( $current_iteration );

		$context = new ExecuteContext( $this->execute, $model );
		$context->setTrace( $trace );

		$this->execute->schedule( $model, $context );

		$output->writeln( '<comment>Endpoint scheduled to continua at ' . $last_iteration . '</comment>: <info>' . $endpoint . '</info>' );

		return Command::SUCCESS;
	}
}
