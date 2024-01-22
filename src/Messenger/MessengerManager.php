<?php

namespace SyncEngine\Messenger;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Worker;
use SyncEngine\Service\System;

class MessengerManager implements EventSubscriberInterface
{
	public function __construct(
		#[Autowire( '%env(int:MESSENGER_LIMIT)%' )]
		private readonly ?int $limit,
		#[Autowire( '%env(int:MESSENGER_TIME_LIMIT)%' )]
		private readonly ?int $timeLimit,
		private readonly KernelInterface $kernel,
		private readonly System $system,
		#[Autowire( '@messenger.receiver_locator' )]
		private readonly ContainerInterface $transportLocator,
	) {}

	public function callCommand( $command ): void
	{
		$process = $this->system->getCommandProcess( $command, true );
		//$process->disableOutput(); // UnixPipes will open /dev/null event in basedir restriction.
		$process->setTimeout( 0 );
		$process->run();
	}

	/**
	 * @see StatsCommand
	 */
	public function hasQueue( $transportNames ): bool
	{
		foreach ( (array) $transportNames as $transportName ) {
			$transport = $this->transportLocator->get( $transportName );

			if ( $transport->getMessageCount() ) {
				return true;
			}
		}

		return false;
	}

	public function getWorkerPath()
	{
		$fs = new Filesystem();
		$dir = $this->kernel->getProjectDir() . '/var/process';
		$file = $dir . '/workers.json';

		if ( ! $fs->exists( $file ) ) {
			$fs->mkdir( $dir );
			$fs->touch( $file );
		}

		return $file;
	}

	public function setWorkers( $data )
	{
		( new Filesystem() )->dumpFile( $this->getWorkerPath(), json_encode( $data ) );
	}

	public function getWorkers( $transport = null ): array|int
	{
		$content = file_get_contents( $this->getWorkerPath() );

		$workers = json_decode( $content, true );

		if ( ! $workers ) {
			return $transport ? 0 : [];
		}

		if ( $transport ) {
			return (int) ( $workers[ $transport ] ?? 0 );
		}

		return $workers;
	}

	public function startWorker( $transport ): void
	{
		if ( ! $this->getWorkers( $transport ) ) {

			$command = [ 'messenger:consume', $transport, '--time-limit=' . ( $this->timeLimit ?: 3600 ) ];

			if ( $this->limit ) {
				$command[] = '--limit=' . $this->limit;
			}

			$this->callCommand( $command );
		}
	}

	public function stopAllWorkers(): void
	{
		$this->callCommand( [ 'messenger:stop-workers' ] );
	}

	public function registerWorker( Worker $worker ): void
	{
		$transportNames = $worker->getMetadata()->getTransportNames();

		$workers = $this->getWorkers();

		foreach ( $transportNames as $transport ) {
			if ( ! isset( $workers[ $transport ] ) ) {
				$workers[ $transport ] = 1;
			} else {
				$workers[ $transport ] += 1;
			}
		}

		$this->setWorkers( $workers );
	}

	public function unregisterWorker( Worker $worker ): void
	{
		$transportNames = $worker->getMetadata()->getTransportNames();

		$workers = $this->getWorkers();

		foreach ( $transportNames as $transport ) {
			if ( isset( $workers[ $transport ] ) ) {
				$workers[ $transport ] -= 1;
				if ( 1 > $workers[ $transport ] ) {
					// Do not allow below 0.
					$workers[ $transport ] = 0;
				}
			}
		}

		$this->setWorkers( $workers );
	}

	public function onWorkerStarted( WorkerStartedEvent $event ): void
	{
		$this->registerWorker( $event->getWorker() );
	}

	public function onWorkerRunning( WorkerRunningEvent $event ): void
	{
		$transport = $event->getWorker()->getMetadata()->getTransportNames();
		if ( $event->isWorkerIdle() && ! $this->hasQueue( $transport ) ) {
			$event->getWorker()->stop();
		}
	}

	public function onWorkerStopped( WorkerStoppedEvent $event ): void
	{
		$transport = $event->getWorker()->getMetadata()->getTransportNames();
		$this->unregisterWorker( $event->getWorker() );
		if ( $this->hasQueue( $transport ) ) {
			$this->startWorker( $transport );
		}
	}

	public function onSendMessageToTransportsEvent( SendMessageToTransportsEvent $event ): void
	{
		// @todo Custom transports?
		$this->startWorker( 'async' );
	}

	public static function getSubscribedEvents(): array
	{
		return [
			WorkerStartedEvent::class => 'onWorkerStarted',
			WorkerRunningEvent::class => 'onWorkerRunning',
			WorkerStoppedEvent::class => 'onWorkerStopped',
			SendMessageToTransportsEvent::class => 'onSendMessageToTransportsEvent',
		];
	}
}
