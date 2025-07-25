<?php

namespace SyncEngine\Messenger;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Worker;

class WorkerRegistry
{
	private static ?int $_registered_worker_pid = null;

	public function __construct(
		private readonly KernelInterface $kernel,
	) {}

	public function getCurrentWorkerPid(): ?int
	{
		return self::$_registered_worker_pid;
	}

	public function getWorkerRegistryDir( $trail = true, $subdir = '' ): string
	{
		$fs  = new Filesystem();
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
		$fs   = new Filesystem();
		$dir  = $this->kernel->getProjectDir() . '/var/process';
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

	public function cleanupWorkerRegistry(): void
	{
		$workers = $this->getWorkerRegistry();

		if ( empty( $workers['__pid'] ) ) {
			$this->setWorkersRegistry( [] );
			( new Filesystem() )->remove( $this->getWorkerPingDir() );
			return;
		}

		$processes = array_keys( $workers['__pid'] );

		foreach ( $processes as $pid ) {
			$ping = $this->getWorkerPing( $pid );
			if ( $ping < ( time() - 3600 ) ) {
				$this->unregisterWorker( null, $pid );
			}
		}

		$dir   = $this->getWorkerPingDir();
		$files = scandir( $dir );
		if ( ! $files ) {
			return;
		}

		$fs = new Filesystem();
		foreach ( $files as $file ) {
			if ( $file === '.' || $file === '..' ) {
				continue;
			}
			if ( ! in_array( $file, $processes, true ) ) {
				$fs->remove( $dir . $file );
			}
		}
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
}
