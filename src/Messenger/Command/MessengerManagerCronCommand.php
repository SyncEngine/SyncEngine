<?php

namespace SyncEngine\Messenger\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use SyncEngine\Messenger\MessengerManager;

#[AsCommand(
	name: 'syncengine:messenger:manager:cron',
	description: 'Run SyncEngine Messenger Cron',
)]
class MessengerManagerCronCommand extends Command
{
	public function __construct(
		#[Autowire( 'console.command.messenger_consume_messages' )]
		private readonly ConsumeMessagesCommand $command,
		private readonly MessengerManager $manager,
	) {
		parent::__construct();
	}

	protected function configure()
	{
		$this->command->configure();

		$this->setDefinition( $this->command->getDefinition() );
		$this->setHelp( $this->command->getHelp() );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		if ( ! $this->manager->isCron() ) {
			return Command::FAILURE;
		}

		$arguments = [
			'receivers' => 'async',
		];
		foreach ( $arguments as $name => $value ) {
			try {
				if ( $input->getArgument( $name ) ) {
					continue;
				}
			} catch ( \Throwable $e ) {
				// Nothing wrong here.
			}

			$input->setArgument( $name, $value );
		}

		$options = $this->manager->getCommandOptions();
		foreach ( $options as $name => $value ) {
			try {
				if ( $input->getOption( $name ) ) {
					continue;
				}
			} catch ( \Throwable $e ) {
				// Nothing wrong here.
			}

			$input->setOption( $name, $value );
		}

		return $this->command->execute( $input, $output );
	}
}
