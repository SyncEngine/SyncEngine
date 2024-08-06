<?php

namespace SyncEngine\Messenger\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Messenger\MessengerManager;

#[AsCommand(
	name: 'syncengine:messenger:manager:stop',
	description: 'Stop SyncEngine Messenger Manager',
)]
class MessengerManagerStopCommand extends Command
{
	public function __construct( private readonly MessengerManager $manager )
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		if ( ! $this->manager->isInternal() ) {
			return Command::FAILURE;
		}

		$this->manager->disable();

		return Command::SUCCESS;
	}
}
