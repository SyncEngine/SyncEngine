<?php

namespace SyncEngine\Messenger\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Messenger\MessengerManager;
use SyncEngine\Service\System;

#[AsCommand(
	name: 'syncengine:messenger:manager:cron',
	description: 'Run SyncEngine Messenger Cron',
)]
class MessengerManagerCronCommand extends Command
{
	public function __construct(
		private readonly System $system,
		private readonly MessengerManager $manager,
	) {
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		if ( ! $this->manager->isCron() ) {
			return Command::INVALID;
		}

		$arguments = [ 'receivers' => $this->manager->getManagedTransports() ];
		$options = $this->manager->getCommandOptions();

		$response = $this->system->runCommand( 'messenger:consume', $arguments, $options, false );

		$output->write( $response['output'] );

		return $response['success'] ? Command::SUCCESS : Command::FAILURE;
	}
}
