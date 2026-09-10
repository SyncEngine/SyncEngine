<?php

namespace SyncEngine\Command\Tmp;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:execute:dev:update:rename-trace-data-column',
	description: 'Fix trace data column since 2026-09-08',
)]
class FixTraceDataColumnCommand extends Command
{
	// @phpstan-ignore-next-line property.onlyWritten (Required to trigger DI container initialization)
	public function __construct( private EntityManagerInterface $entityManager )
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		try {
			$this->entityManager->getConnection()->executeStatement('ALTER TABLE trace CHANGE trace data LONGTEXT NOT NULL');
		} catch (\Exception $e) {
			$output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}
}
