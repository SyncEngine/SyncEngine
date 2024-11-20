<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:automation',
	description: 'List available automations',
)]
class AutomationCommand extends Command
{

	public function __construct( DefaultController $controller )
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
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
}
