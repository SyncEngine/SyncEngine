<?php

namespace SyncEngine\Messenger\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Contracts\Service\Attribute\Required;
use SyncEngine\Messenger\MessengerManager;

#[AsCommand(
	name: 'syncengine:messenger:manager:cron',
	description: 'Start SyncEngine Messenger Manager',
)]
class MessengerManagerCronCommand extends ConsumeMessagesCommand
{
	private MessengerManager $manager;

	#[Required]
	public function setManager( MessengerManager $manager )
	{
		$this->manager = $manager;
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

		return parent::execute( $input, $output );
	}
}
