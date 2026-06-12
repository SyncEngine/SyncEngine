<?php

namespace SyncEngine\Command\Tmp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\ConnectionModel;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:execute:dev:update:config-automation-actions',
	description: 'Fix Automation Actions config format since 2026-03-12',
)]
class FixAutomationActionsConfig extends Command
{
	public function __construct( DefaultController $controller )
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$models = AutomationModel::getAll();

		foreach ( $models as $model ) {
			$actions = $model->getConfig('actions');

			if ( is_array( $actions ) ) {
				$model->setConfig( $actions, 'tasks' );
				$model->setConfig( 'tasks', 'actions' );
			}

			$model->update( true );
		}

		return Command::SUCCESS;
	}
}
