<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

if ( ! defined( 'STDIN' ) ) {
	define( 'STDIN', fopen( "php://stdin", "r" ) );
}

class MessengerManager
{
	public function __construct(
		#[Autowire( '%kernel.project_dir%' )]
		private readonly string $projectDir, private readonly KernelInterface $kernel,
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
		$command = 'php bin/console messenger:consume async --time-limit=3600';

		// @todo WTF WINDOWS.
		if ( str_starts_with( strtoupper( PHP_OS ), "WIN" ) ) {
			$command = 'start /b ' . $command;
		} else {
			$command .= ' &';
		}

		$this->callCommand( $command );
	}

	public function stop(): void
	{
		$this->callCommand( 'php bin/console messenger:stop-workers' );
	}

	public function callCommand( $command ): void
	{
		$process = Process::fromShellCommandline( $command );
		$process->setWorkingDirectory( $this->projectDir );
		$process->disableOutput();
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
