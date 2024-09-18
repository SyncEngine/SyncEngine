<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Service\Execute;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:schedule:endpoint',
	description: 'Schedule automation endpoint',
)]
class ScheduleEndpointCommand extends Command
{
	private Execute $execute;

	public function __construct( Execute $execute, DefaultController $controller )
	{
		$this->execute = $execute;
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addArgument( 'endpoint', InputArgument::REQUIRED, 'The automation endpoint.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$endpoint = $input->getArgument( 'endpoint' );

		$model = AutomationModel::get( [ 'endpoint' => $endpoint ] );

		if ( ! $model ) {
			$output->writeln( 'Endpoint not found: ' . $endpoint );
			return Command::INVALID;
		}

		$this->execute->schedule( $model );

		$output->writeln( 'Endpoint scheduled: ' . $endpoint );

		return Command::SUCCESS;
	}
}
