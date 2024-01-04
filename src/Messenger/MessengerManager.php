<?php

namespace SyncEngine\Messenger;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\KernelInterface;
use SyncEngine\Service\System;

if ( ! defined( 'STDIN' ) ) {
	define( 'STDIN', fopen( "php://stdin", "r" ) );
}

class MessengerManager
{
	public function __construct(
		#[Autowire( '%env(int:MESSENGER_LIMIT)%' )]
		private readonly ?int $limit,
		#[Autowire( '%env(int:MESSENGER_TIME_LIMIT)%' )]
		private readonly ?int $timeLimit,
		private readonly KernelInterface $kernel,
		private readonly System $system,
	) {}

	public function handleQueue(): void
	{
		$hasQueue = $this->hasQueue();
		if ( $hasQueue ) {
			$this->start();
		}

		$hasQueue = $this->hasQueue();
		if ( ! $hasQueue ) {
			$this->stop();
		}
	}

	public function start(): void
	{
		$command = [ 'messenger:consume', 'async', '--time-limit=' . $this->timeLimit ?: 3600 ];

		if ( $this->limit ) {
			$command[] = '--limit=' . $this->limit;
		}

		$this->callCommand( $command );
	}

	public function stop(): void
	{
		$this->callCommand( [ 'messenger:stop-workers' ] );
	}

	public function callCommand( $command ): void
	{
		$process = $this->system->getCommandProcess( $command, true );
		//$process->disableOutput(); // UnixPipes will open /dev/null event in basedir restriction.
		$process->setTimeout( 0 );
		$process->run();
	}

	public function hasQueue(): bool
	{
		$application = new Application( $this->kernel );
		$input       = new ArrayInput( [ 'command' => 'messenger:stats' ] );
		$output      = new BufferedOutput();

		$application->setAutoExit( false );
		$application->run( $input, $output );

		$content = $output->fetch();

		// Note that there are multiple lines so the returned number isn't the number of items in the queue.
		return 0 < (int) preg_replace( "/\D/", '', $content );
	}
}
