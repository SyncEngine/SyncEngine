<?php

namespace SyncEngine\Messenger;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Command\StopWorkersCommand;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Worker;
use SyncEngine\EventDispatcher\Event\ExecuteEvent;
use SyncEngine\Service\System;

class MessengerManager implements EventSubscriberInterface
{
	const MANAGER_INTERNAL = 'syncengine';
	const MANAGER_CRON = 'cron';
	const MANAGER_EXTERNAL = 'external';

	private static string $_autostart_disabled_file = 'workers-autostart.disabled';
	private static ?int $_registered_worker_pid = null;

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
		private readonly KernelInterface $kernel,
		private readonly System $system,
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
			return ! ( new Filesystem() )->exists( $this->getWorkerRegistryDir() . self::$_autostart_disabled_file );
		}
		return false;
	}

	public function disable( $stopAllWorkers = true ): void
	{
		( new Filesystem() )->touch( $this->getWorkerRegistryDir() . self::$_autostart_disabled_file );

		if ( $stopAllWorkers ) {
			$this->stopAllWorkers();
		}
	}

	public function enable( $autoStartWorker = true ) : void
	{
		( new Filesystem() )->remove( $this->getWorkerRegistryDir() . self::$_autostart_disabled_file );

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

	public function getWorkerRegistryDir( $trail = true, $subdir = '' ): string
	{
		$fs = new Filesystem();
		$dir = $this->kernel->getProjectDir() . '/var/process';

		if ( $subdir ) {
			$dir .= '/' . $subdir;
		}

		if ( ! $fs->exists( $dir ) ) {
			$fs->mkdir( $dir );
		}

		return $trail ? $dir . '/' : $dir;
	}

	public function getWorkerRegistryFile(): string
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

	public function getWorkerPingDir( $trail = true ): string
	{
		return $this->getWorkerRegistryDir( $trail, 'workers' );
	}

	public function getWorkerPingFile( ?int $pid = null ): string
	{
		$pid ??= getmypid();
		return $this->getWorkerPingDir() . $pid;
	}

	public function setWorkersRegistry( $data ): void
	{
		( new Filesystem() )->dumpFile( $this->getWorkerRegistryFile(), json_encode( $data ) );
	}

	public function getWorkerRegistry(): array
	{
		$content = file_get_contents( $this->getWorkerRegistryFile() );

		$workers = json_decode( $content, true );

		if ( ! $workers ) {
			return [];
		}

		return $workers;
	}

	public function getWorkerProcesses( string|array|null $transport = null ): array
	{
		$this->cleanupWorkerRegistry();

		$workers = $this->getWorkerRegistry();

		if ( ! $transport ) {
			return $workers['__pid'] ?? [];
		}

		if ( is_array( $transport ) ) {
			$transport = implode( ' ', $transport );
		}

		$found = [];
		foreach ( $workers['__pid'] ?? [] as $pid => $worker ) {
			if ( $worker['transports'] === $transport ) {
				$found[ $pid ] = $worker;
			}
		}

		return $found;
	}

	public function getWorkerPing( $pid ): false|string
	{
		$ping = $this->getWorkerPingFile( $pid );
		if ( ! file_exists( $ping ) ) {
			return false;
		}
		return file_get_contents( $ping );
	}

	public function getWorkerCount( string|array $transport ): int
	{
		$workers       = $this->getWorkerRegistry();
		$transportList = $workers['__transports'] ?? [];

		if ( is_array( $transport ) ) {
			$transport = implode( ' ', $transport );
		}

		return (int) ( $transportList[ $transport ] ?? 0 );
	}

	public function autoStartWorker( null|string|array $transports = null ): void
	{
		if ( ! $this->isInternal() ) {
			return;
		}

		if ( null === $transports ) {
			$transports = $this->getManagedTransports();
		}

		if ( $this->workerLimit > $this->getWorkerCount( $transports ) ) {

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

	public function cleanupWorkerRegistry(): void
	{
		$workers = $this->getWorkerRegistry();

		if ( empty( $workers['__pid'] ) ) {
			$this->setWorkersRegistry( [] );
			( new Filesystem() )->remove( $this->getWorkerPingDir() );
			return;
		}

		foreach ( $workers['__pid'] as $pid => $worker ) {
			$ping = $this->getWorkerPing( $pid );
			if ( $ping < ( time() - 3600 ) ) {
				$this->unregisterWorker( null, $pid );
			}
		}
	}

	public function registerWorker( Worker $worker ): void
	{
		$transportNames = $worker->getMetadata()->getTransportNames();

		$workers = $this->getWorkerRegistry();
		$pid     = getmypid();

		$workers['__pid'][ $pid ] = [
			'transports' => implode( ' ', $transportNames ),
			'timestamp'  => time(),
		];

		if ( 1 < count( $transportNames ) ) {
			$transportNames[] = implode( ' ', $transportNames );
		}

		$transportList = $workers['__transports'] ?? [];

		foreach ( $transportNames as $transport ) {
			if ( ! isset( $transportList[ $transport ] ) ) {
				$transportList[ $transport ] = 1;
			} else {
				$transportList[ $transport ] += 1;
			}
		}

		$workers['__transports'] = $transportList;

		$this->setWorkersRegistry( $workers );

		$this::$_registered_worker_pid = $pid;
	}

	public function pingWorker( Worker $worker ): void
	{
		$fs   = new Filesystem();
		$pid  = getmypid();
		$file = $this->getWorkerPingFile( $pid );


		if ( ! $fs->exists( $file ) ) {
			$fs->touch( $file );
		}

		$fs->dumpFile( $file, time() );
	}

	public function unregisterWorker( ?Worker $worker, ?int $pid = null ): void
	{
		$workers = $this->getWorkerRegistry();

		if ( ! $workers ) {
			return;
		}

		$pid ??= getmypid();
		$transportNames = [];

		if ( $worker instanceof Worker ) {
			$transportNames = $worker->getMetadata()->getTransportNames();
			if ( 1 < count( $transportNames ) ) {
				$transportNames[] = implode( ' ', $transportNames );
			}
		}

		if ( isset( $workers['__pid'][ $pid ]['transports'] ) ) {
			$transports     = explode( ' ', $workers['__pid'][ $pid ]['transports'] );
			$transportNames = array_merge( $transports, (array) $transportNames );
			if ( 1 < count( $transports ) ) {
				$transportNames[] = implode( ' ', $transports );
			}
		}

		unset( $workers['__pid'][ $pid ] );

		( new Filesystem() )->remove( $this->getWorkerPingFile( $pid ) );

		$transportList = $workers['__transports'] ?? [];

		foreach ( $transportNames as $transport ) {
			if ( isset( $transportList[ $transport ] ) ) {
				$transportList[ $transport ] -= 1;
				if ( 1 > $transportList[ $transport ] ) {
					// Do not allow below 0.
					$transportList[ $transport ] = 0;
				}
			}
		}

		$workers['__transports'] = $transportList;

		$this->setWorkersRegistry( $workers );
	}

	public function onWorkerStarted( WorkerStartedEvent $event ): void
	{
		$this->registerWorker( $event->getWorker() );
	}

	public function onWorkerRunning( WorkerRunningEvent $event ): void
	{
		$this->pingWorker( $event->getWorker() );

		// @link https://dev.to/fadymr/use-symfony-messenger-without-supervisor-3cl6
		if ( $this->isCron() && $event->isWorkerIdle() ) {
			$event->getWorker()->stop();
			return;
		}

		// Stop if there are more active workers than the configured limit.
		$transports = $event->getWorker()->getMetadata()->getTransportNames();
		if ( $this->workerLimit && $this->workerLimit > $this->getWorkerCount( $transports ) ) {
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
		$this->unregisterWorker( $event->getWorker() );

		if ( ! $this->isEnabled() ) {
			return;
		}

		$transports = $event->getWorker()->getMetadata()->getTransportNames();
		if ( ! $this->getWorkerCount( $transports ) ) {
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

		if ( $this::$_registered_worker_pid !== $pid || $debounce >= $time ) {
			return;
		}

		if ( ! $file ) {
			$fs   = new Filesystem();
			$file = $this->getWorkerPingFile( $pid );
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
