<?php

namespace SyncEngine\Command\Tmp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\TraceModel;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:execute:trace:relocate',
	description: 'Fix trace dirs since 2024-09-11',
)]
class FixTraceDirCommand extends Command
{
	public function __construct( DefaultController $controller )
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$fs = new Filesystem();
		$traces = TraceModel::getAll();

		foreach ( $traces as $trace ) {
			$newdir = $trace->getTraceDir();

			$olddir = dirname( rtrim( $newdir, '/' ) ) . '/';

			$finder = new Finder();
			$finder->in( $olddir )->name( $trace->getTraceDirname() . '_*' )->depth( 0 );

			foreach ( $finder as $file ) {
				$fs->rename( $file->getPathname(), $newdir . $file->getRelativePathname() );
				$output->writeln( 'Moved: ' . $file->getRelativePathname() . ' > ' . basename( $newdir ) . '/' . $file->getRelativePathname() );
			}
		}

		return Command::SUCCESS;
	}
}
