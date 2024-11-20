<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Model\AutomationModel;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:automation:reset',
	description: 'Reset automation running and iterator',
)]
class AutomationResetCommand extends AutomationCommand
{
	protected function configure(): void
	{
		$this->addArgument( 'automation', InputArgument::OPTIONAL, 'The automation ID or Ref.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$automation = $input->getArgument( 'automation' );

		if ( ! $automation ) {
			return parent::execute( $input, $output );
		}

		$model = AutomationModel::get( $automation );

		if ( ! $model ) {
			$output->writeln( '<error>Automation not found</error>: <info>' . $automation . '</info>' );
			return Command::INVALID;
		}

		$success = false;
		try {
			$model->reset();
			$model->update( true );
			$success = true;
			$output->writeln( '<comment>Automation reset</comment>: <info>' . $model->getName() . '</info> (ID: <info>' . $model->getId() . '</info> | Ref: <info>' . $model->getRef() . '</info>)' );
		} catch ( \Throwable $e ) {
			$output->writeln( '<error>' . $e->getMessage() . '</error>' );
		}

		return ( $success ) ? Command::SUCCESS : Command::FAILURE;
	}
}
