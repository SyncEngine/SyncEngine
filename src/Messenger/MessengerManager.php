<?php

namespace SyncEngine\Messenger;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Command\StopWorkersCommand;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use SyncEngine\EventDispatcher\Event\ExecuteEvent;
use SyncEngine\Service\System;

class MessengerManager implements EventSubscriberInterface
{
	const MANAGER_INTERNAL = 'syncengine';
	const MANAGER_CRON = 'cron';
	const MANAGER_EXTERNAL = 'external';

	private static string $_autostart_disabled_file = 'workers-autostart.disabled';

	public function __construct(
		#[Autowire( '%env(string:SYNCENGINE_MESSENGER_MANAGER)%' )]
		private readonly string $manager,
		#[Autowire( '%env(int:SYNCENGINE_MESSENGER_WORKER_LIMIT)%' )]
		private readonly ?int $workerLimit,
		#[Autowire( '%env(int:SYNCENGINE_MESSENGER_WORKER_QUEUE_LIMIT)%' )]
		private readonly ?int $queueLimit,
		#[Autowire( '%env(int:SYNCENGINE_MESSENGER_WORKER_TIME_LIMIT)%' )]
		private readonly ?int $timeLimit,
		#[Autowire( '%env(int:SYNCENGINE_MESSENGER_WORKER_MEMORY_LIMIT)%' )]
		private readonly ?int $memoryLimit,
		#[Autowire( '@messenger.receiver_locator' )]
		private readonly ContainerInterface $transportLocator,
		private readonly System $system,
		private readonly WorkerRegistry $workerRegistry,
	) {}

	public function callCommand( $command ): void
	{
		$process = $this->system->getCommandProcess( $command, true );
		//$process->disableOutput(); // UnixPipes will open /dev/null event in basedir restriction.
		$process->setTimeout( 0 );
		$process->run();
	}

	public function validate(): bool|string
	{
		if ( $this->isInternal() ) {
			try {
				$this->system->getPhpExecutable();
			} catch ( \Throwable $e ) {
				$this->disable( false );
				return $e->getMessage();
			}
		}

		return true;
	}

	public function getManagedTransports(): array
	{
		return [ 'async' ];
	}

	public function getCommandOptions(): array
	{
		$options = [
			'time-limit' => $this->timeLimit ?: 3600,
		];

		if ( $this->queueLimit ) {
			$options['limit'] = $this->queueLimit;
		}

		if ( $this->memoryLimit ) {
			$options['memory-limit'] = $this->memoryLimit . 'M';
		}

		return $options;
	}

	public function getManager(): string
	{
		return $this->manager;
	}

	public function isInternal(): bool
	{
		return self::MANAGER_INTERNAL === $this->manager;
	}

	public function isCron(): bool
	{
		return self::MANAGER_CRON === $this->manager;
	}

	public function isExternal(): bool
	{
		return ! $this->isInternal() && ! $this->isCron();
	}

	public function isEnabled(): bool
	{
		if ( $this->isInternal() ) {
			// Always re-check for file existence.
			return ! ( new Filesystem() )->exists( $this->workerRegistry->getWorkerRegistryDir() . self::$_autostart_disabled_file );
		}
		return false;
	}

	public function disable( $stopAllWorkers = true ): void
	{
		( new Filesystem() )->touch( $this->workerRegistry->getWorkerRegistryDir() . self::$_autostart_disabled_file );

		if ( $stopAllWorkers ) {
			$this->stopAllWorkers();
		}
	}

	public function enable( $autoStartWorker = true ) : void
	{
		( new Filesystem() )->remove( $this->workerRegistry->getWorkerRegistryDir() . self::$_autostart_disabled_file );

		if ( $autoStartWorker ) {
			if ( true === $autoStartWorker ) {
				$autoStartWorker = $this->getManagedTransports();
			}
			$this->autoStartWorker( $autoStartWorker );
		}
	}

	public function getQueueCount( $transport ): int
	{
		$transport = $this->transportLocator->get( $transport );
		/** @var TransportInterface $transport */

		if ( $transport instanceof MessageCountAwareInterface ) {
			return $transport->getMessageCount();
		}

		return 0;
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

	public function autoStartWorker( null|string|array $transports = null ): void
	{
		if ( ! $this->isInternal() ) {
			return;
		}

		if ( null === $transports ) {
			$transports = $this->getManagedTransports();
		}

		if ( $this->workerLimit > $this->workerRegistry->getWorkerCount( $transports ) ) {

			/**
			 * @see ConsumeMessagesCommand
			 */
			$command = [ 'messenger:consume' ];

			foreach ( (array) $transports as $transport ) {
				$command[] = $transport;
			}

			$options = $this->getCommandOptions();
			foreach ( $options as $name => $value ) {
				$command[] = '--' . $name . '=' . $value;
			}

			$this->callCommand( $command );
		}
	}

	public function stopAllWorkers(): void
	{
		/**
		 * @see StopWorkersCommand
		 */
		$this->callCommand( [ 'messenger:stop-workers' ] );
	}

	public function onWorkerStarted( WorkerStartedEvent $event ): void
	{
		$this->workerRegistry->registerWorker( $event->getWorker() );
	}

	public function onWorkerRunning( WorkerRunningEvent $event ): void
	{
		$this->workerRegistry->pingWorker( $event->getWorker() );

		// @link https://dev.to/fadymr/use-symfony-messenger-without-supervisor-3cl6
		if ( $this->isCron() && $event->isWorkerIdle() ) {
			$event->getWorker()->stop();
			return;
		}

		// Stop if there are more active workers than the configured limit.
		$transports = $event->getWorker()->getMetadata()->getTransportNames();
		if ( $this->workerLimit && $this->workerLimit > $this->workerRegistry->getWorkerCount( $transports ) ) {
			$event->getWorker()->stop();
		}

		// @todo Find a way to check if the current queue is actually empty (also no messages in the future).
		/*
		if ( $event->isWorkerIdle() && ! $this->hasQueue( $transports ) ) {
			$event->getWorker()->stop();
		}*/
	}

	public function onWorkerStopped( WorkerStoppedEvent $event ): void
	{
		$this->workerRegistry->unregisterWorker( $event->getWorker() );

		if ( ! $this->isEnabled() ) {
			return;
		}

		$transports = $event->getWorker()->getMetadata()->getTransportNames();
		if ( ! $this->workerRegistry->getWorkerCount( $transports ) ) {
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

	public function onExecuteEvent(): void
	{
		static $debounce = 0, $file = null, $fs = null;

		$time = time();
		$pid  = getmypid();

		if ( $this->workerRegistry->getCurrentWorkerPid() !== $pid || $debounce >= $time ) {
			return;
		}

		if ( ! $file ) {
			$fs   = new Filesystem();
			$file = $this->workerRegistry->getWorkerPingFile( $pid );
		}

		if ( ! $fs->exists( $file ) ) {
			$fs->touch( $file );
		}

		$fs->dumpFile( $file, time() );

		$debounce = $time;
	}

	public static function getSubscribedEvents(): array
	{
		return [
			SendMessageToTransportsEvent::class => 'onSendMessageToTransportsEvent',
			WorkerStartedEvent::class => 'onWorkerStarted',
			WorkerRunningEvent::class => 'onWorkerRunning',
			WorkerStoppedEvent::class => 'onWorkerStopped',
			ExecuteEvent::class => 'onExecuteEvent',
		];
	}
}
