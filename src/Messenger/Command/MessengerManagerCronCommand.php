<?php

namespace SyncEngine\Messenger\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use SyncEngine\Messenger\MessengerManager;

#[AsCommand(
	name: 'syncengine:messenger:manager:cron',
	description: 'Run SyncEngine Messenger Cron',
)]
class MessengerManagerCronCommand extends Command
{
	public function __construct(
		private readonly MessengerManager $manager,
		private readonly ConsumeMessagesCommand $command
	) {
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		if ( ! $this->manager->isCron() ) {
			return Command::FAILURE;
		}

		$options = $this->manager->getCommandOptions();

		foreach ( $options as $name => $value ) {
			if ( ! $input->getOption( $name ) ) {
				$input->setOption( $name, $value );
			}
		}

		return $this->command->execute( $input, $output );
	}
}
