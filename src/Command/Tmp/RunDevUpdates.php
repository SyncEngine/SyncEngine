<?php

namespace SyncEngine\Command\Tmp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @return void
 */
#[AsCommand( name: 'syncengine:execute:dev:updates', description: 'Run through all tmp dev updates.', )]
class RunDevUpdates extends Command
{
	public function __construct()
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$commands = [
			'2024-09-11' => 'syncengine:execute:trace:relocate',
			'2024-10-02' => 'syncengine:execute:webservice:update:config:http',
			'2025-06-11' => 'syncengine:execute:rename:routine',
		];

		$helper = $this->getHelper( 'question' );

		foreach ( $commands as $date => $commandName ) {
			$question = new ConfirmationQuestion(
				sprintf( '<comment>Execute</comment> <info>%s</info> created at <info>%s</info>? [y/n] ', $commandName, $date ), false
			);

			if ( $helper->ask( $input, $output, $question ) ) {
				$command = $this->getApplication()->find( $commandName );

				$command->run( new ArrayInput( [ 'command' => $commandName ] ), $output );
				$output->writeln( sprintf( '<comment>Command executed</comment> <info>%s</info>.', $commandName ) );
			} else {
				$output->writeln( 'Skipping...' );
			}
		}

		$output->writeln( 'Done!' );
		return Command::SUCCESS;
	}
}
