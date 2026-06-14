<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Service\Generator\Ref;
use SyncEngine\Service\System;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:system:repair',
	description: 'Repair system script',
)]
class SystemRepairCommand extends Command
{
	public function __construct( private readonly System $system, DefaultController $controller ) {
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$result = $this->system->runDatabaseRepair( false );

		if ( $result instanceof \Throwable ) {
			$output->writeln( '<error>' . $result->getMessage() . '</error>' );
		} else {
			$result = is_bool( $result ) ? $result : $result['success'] ?? false;

			$output->writeln( $result ? '<info>Database repaired successfully.</info>' : '<error>Database repair failed.</error>' );
		}

		return Command::SUCCESS;
	}
}
