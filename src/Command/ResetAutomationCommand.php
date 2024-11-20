<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:reset:automation',
	description: 'Reset automation running and iterator',
)]
class ResetAutomationCommand extends Command
{
	public function __construct( DefaultController $controller )
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addArgument( 'automation', InputArgument::OPTIONAL, 'The automation ID or Ref.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$automation = $input->getArgument( 'automation' );

		if ( ! $automation ) {
			$automations = AutomationModel::getAll( [] );

			$output->writeln( '<comment>Available automations:</comment>' );
			$table = new Table( $output );

			$table->setHeaders(['ID', 'Ref', 'Name']);
			foreach ( $automations as $automation ) {
				// @todo Access validation when implemented.
				$table->addRow([$automation->getId(), $automation->getRef(), $automation->getName()]);
			}
			$table->render();

			return Command::SUCCESS;
		}

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
