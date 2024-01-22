<?php

namespace SyncEngine\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Transport\TransportInterface;
use SyncEngine\Controller\DefaultController;

#[AsCommand(
	name: 'syncengine:messenger:stats',
	description: 'Run SyncEngine cron queue',
)]
class DevCommand extends Command
{
	public function __construct(
		#[Autowire( '@messenger.receiver_locator' )]
		private readonly ContainerInterface $transportLocator,DefaultController $controller)
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output )
	{
		$transport = $this->transportLocator->get( 'async' );
		/** @var TransportInterface $transport */

		$envelope = $transport->get();
		var_dump( $envelope );
		foreach ( $envelope as $item ) {
			$output->write( $item->all() );
		}

		return Command::SUCCESS;
	}
}
