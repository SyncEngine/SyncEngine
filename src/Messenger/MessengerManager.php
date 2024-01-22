<?php

namespace SyncEngine\Messenger;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Worker;
use SyncEngine\Service\System;

class MessengerManager implements EventSubscriberInterface
{
	public bool $enabled;

	public function __construct(
		#[Autowire( '%env(int:SYNCENGINE_MESSENGER_MANAGER)%' )]
		private readonly string $manager,
		#[Autowire( '%env(int:SYNCENGINE_MESSENGER_WORKER_LIMIT)%' )]
		private readonly ?int $workerLimit,
		#[Autowire( '%env(int:SYNCENGINE_MESSENGER_WORKER_QUEUE_LIMIT)%' )]
		private readonly ?int $queueLimit,
		#[Autowire( '%env(int:SYNCENGINE_MESSENGER_WORKER_TIME_LIMIT)%' )]
		private readonly ?int $timeLimit,
		#[Autowire( '%env(int:SYNCENGINE_MESSENGER_WORKER_MEMORY_LIMIT)%' )]
		private readonly ?int $memoryLimit,
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

	public function isEnabled(): bool
	{
		if ( ! isset( $this->enabled ) ) {
			$this->enabled = false;
			if ( 'syncengine' === $this->manager ) {
				$this->enabled = ( new Filesystem() )->exists( $this->getWorkerRegistry() . '.disabled' );
			}
		}
		return $this->enabled;
	}

	public function disableManager(): void
	{
		( new Filesystem() )->touch( $this->getWorkerRegistry() . '.disabled' );
	}

	public function enableManager() : void
	{
		( new Filesystem() )->remove( $this->getWorkerRegistry() . '.disabled' );
	}

	/**
	 * @see StatsCommand
	 */
	public function hasQueue( $transportNames ): bool
	{
		foreach ( (array) $transportNames as $transportName ) {
			$transport = $this->transportLocator->get( $transportName );
			/** @var TransportInterface $transport */

			if ( $transport instanceof MessageCountAwareInterface ) {
				if ( $transport->getMessageCount() ) {
					return true;
				}
			}
		}

		return false;
	}

	public function getWorkerRegistry()
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
		( new Filesystem() )->dumpFile( $this->getWorkerRegistry(), json_encode( $data ) );
	}

	public function getWorkers( string|array $transport = null ): array|int|false
	{
		$content = file_get_contents( $this->getWorkerRegistry() );

		$workers = json_decode( $content, true );

		if ( 'disabled' === $workers ) {
			return false;
		}

		if ( ! $workers ) {
			return $transport ? 0 : [];
		}

		if ( $transport ) {
			if ( is_array( $transport ) ) {
				$transport = implode( ' ', $transport );
			}
			return (int) ( $workers[ $transport ] ?? 0 );
		}

		return $workers;
	}

	public function autoStartWorker( string|array $transports ): void
	{
		if ( 'syncengine' !== $this->manager ) {
			return;
		}

		if ( $this->workerLimit > $this->getWorkers( $transports ) ) {

			$command = [ 'messenger:consume' ];

			foreach ( (array) $transports as $transport ) {
				$command[] = $transport;
			}

			$command[] = '--time-limit=' . ( $this->timeLimit ?: 3600 );

			if ( $this->queueLimit ) {
				$command[] = '--limit=' . $this->queueLimit;
			}

			if ( $this->memoryLimit ) {
				$command[] = '--memory-limit=' . $this->memoryLimit . 'M';
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

		if ( 1 < count( $transportNames ) ) {
			$transportNames[] = implode( ' ', $transportNames );
		}

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

		if ( ! $workers ) {
			return;
		}

		if ( 1 < count( $transportNames ) ) {
			$transportNames[] = implode( ' ', $transportNames );
		}

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
		if ( ! $this->isEnabled() ) {
			return;
		}

		$this->registerWorker( $event->getWorker() );
	}

	public function onWorkerRunning( WorkerRunningEvent $event ): void
	{
		if ( ! $this->isEnabled() ) {
			return;
		}

		// @todo Find a way to check if the current queue is actually empty (also no messages in the future).
		/*$transports = $event->getWorker()->getMetadata()->getTransportNames();
		if ( $event->isWorkerIdle() && ! $this->hasQueue( $transports ) ) {
			$event->getWorker()->stop();
		}*/
	}

	public function onWorkerStopped( WorkerStoppedEvent $event ): void
	{
		if ( ! $this->isEnabled() ) {
			return;
		}

		$transports = $event->getWorker()->getMetadata()->getTransportNames();
		$this->unregisterWorker( $event->getWorker() );
		if ( ! $this->getWorkers( $transports ) ) {
			$this->autoStartWorker( $transports );
		}
	}

	public function onSendMessageToTransportsEvent( SendMessageToTransportsEvent $event ): void
	{
		if ( ! $this->isEnabled() ) {
			return;
		}

		$senders = $event->getSenders();
		$transports = array_keys( $senders );

		$this->autoStartWorker( $transports );
	}

	public function onConsoleCommand( ConsoleCommandEvent $event )
	{
		if ( 'syncengine' !== $this->manager ) {
			return;
		}

		$command = $event->getCommand();
		switch ( $command ) {
			case 'server:start':
				$this->enableManager();
			break;
			case 'server:stop':
				$this->disableManager();
				$this->stopAllWorkers();
			break;
		}
	}

	public static function getSubscribedEvents(): array
	{
		return [
			SendMessageToTransportsEvent::class => 'onSendMessageToTransportsEvent',
			WorkerStartedEvent::class => 'onWorkerStarted',
			WorkerRunningEvent::class => 'onWorkerRunning',
			WorkerStoppedEvent::class => 'onWorkerStopped',
			ConsoleCommandEvent::class => 'onConsoleCommand',
		];
	}
}
