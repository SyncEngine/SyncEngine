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
use SyncEngine\Service\ExecutionContext;

#[AsCommand(
	name: 'syncengine:reset:automation',
	description: 'Reset automation running and iterator',
)]
class ResetAutomationCommand extends Command
{
	private Execute $execute;

	public function __construct( Execute $execute, DefaultController $controller )
	{
		$this->execute = $execute;
		parent::__construct();
	}

	protected function configure()
	{
		$this->addArgument( 'automation', InputArgument::REQUIRED, 'The automation ID or Ref.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output )
	{
		$automation = $input->getArgument( 'automation' );

		$model = AutomationModel::get( $automation );

		if ( ! $model ) {
			$output->writeln( 'Automation not found: ' . $automation );
			return Command::INVALID;
		}

		$success = false;
		try {
			$model->reset();
			$model->update( true );
			$success = true;
		} catch ( \Throwable $e ) {
			$output->writeln( $e->getMessage() );
		}

		return ( $success ) ? Command::SUCCESS : Command::FAILURE;
	}
}
