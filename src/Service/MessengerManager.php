<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

define( 'STDIN', fopen( "php://stdin", "r" ) );

class MessengerManager
{
	public function __construct(
		#[Autowire( '%kernel.project_dir%' )]
		private readonly string $projectDir, private readonly KernelInterface $kernel,
	) {}

	/**
	 * @return bool hasQueue
	 */
	public function handleQueue(): bool
	{
		$hasQueue = $this->hasQueue();
		if ( $hasQueue ) {
			$this->start();
		} else {
			$this->stop();
		}
		return $hasQueue;
	}

	public function start(): void
	{
		$this->callCommand( 'start' );
	}

	public function stop(): void
	{
		$this->callCommand( 'stop' );
	}

	public function callCommand( $action ): void
	{
		if ( 'stop' === $action ) {
			$command = 'php bin/console messenger:stop-workers';
		} else {
			$command = 'php bin/console messenger:consume async --time-limit=3600 &';
		}

		$process = Process::fromShellCommandline( $command );
		$process->setWorkingDirectory( $this->projectDir );
		$process->disableOutput();
		$process->setTimeout( 0 );
		$process->run();
	}

	public function hasQueue(): bool
	{
		$application = new Application( $this->kernel );
		$application->setAutoExit( false );
		$input  = new ArrayInput( [
			'command' => 'messenger:stats',
		] );
		$output = new BufferedOutput();
		$application->run( $input, $output );

		$content = $output->fetch();
		$queued  = ( preg_replace( "/[^0-9]/", '', $content ) == 00 ) ? false : true;

		return $queued;
	}
}
