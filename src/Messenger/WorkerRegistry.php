<?php

namespace SyncEngine\Messenger;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Worker;

class WorkerRegistry
{
	private static ?int $_registered_pid = null;

	public function __construct(
		private readonly KernelInterface $kernel,
	) {}

	public function getRegisteredPid(): ?int
	{
		return self::$_registered_pid;
	}

	public function getDir( $trail = true, $subdir = '' ): string
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

	public function getRegistryFilePath(): string
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

	public function getPingDir( $trail = true ): string
	{
		return $this->getDir( $trail, 'workers' );
	}

	public function getPingFilePath( ?int $pid = null ): string
	{
		$pid ??= getmypid();
		return $this->getPingDir() . $pid;
	}

	public function store( $data ): void
	{
		( new Filesystem() )->dumpFile( $this->getRegistryFilePath(), json_encode( $data ) );
	}

	public function fetch(): array
	{
		$content = file_get_contents( $this->getRegistryFilePath() );

		$workers = json_decode( $content, true );

		if ( ! $workers ) {
			return [];
		}

		return $workers;
	}

	public function cleanup(): void
	{
		$workers = $this->fetch();

		if ( empty( $workers['__pid'] ) ) {
			$this->store( [] );
			( new Filesystem() )->remove( $this->getPingDir() );
			return;
		}

		$processes = array_keys( $workers['__pid'] );

		foreach ( $processes as $pid ) {
			$ping = $this->getWorkerPing( $pid );
			if ( $ping < ( time() - 3600 ) ) {
				$this->unregisterWorker( null, $pid );
			}
		}

		$dir   = $this->getPingDir();
		$files = scandir( $dir );
		if ( ! $files ) {
			return;
		}

		$fs = new Filesystem();
		foreach ( $files as $file ) {
			if ( $file === '.' || $file === '..' ) {
				continue;
			}
			// Non-strict comparison to allow for string or int.
			if ( ! in_array( $file, $processes ) ) {
				$fs->remove( $dir . $file );
			}
		}
	}

	public function getProcesses( string|array|null $transport = null ): array
	{
		$this->cleanup();

		$workers = $this->fetch();

		if ( ! $transport ) {
			return $workers['__pid'] ?? [];
		}

		if ( is_array( $transport ) ) {
			$transport = implode( ' ', $transport );
		}

		if ( ! isset( $workers['__transports'][ $transport ] ) ) {
			return [];
		}

		return array_intersect_key( $workers['__pid'] ?? [], $workers['__transports'][ $transport ] );
	}

	public function getWorkerPing( $pid ): false|string
	{
		$ping = $this->getPingFilePath( $pid );
		if ( ! file_exists( $ping ) ) {
			return false;
		}
		return file_get_contents( $ping );
	}

	public function getWorkerCount( string|array $transport ): int
	{
		$workers       = $this->fetch();
		$transportList = $workers['__transports'] ?? [];

		if ( is_array( $transport ) ) {
			$transport = implode( ' ', $transport );
		}

		return count( $transportList[ $transport ] ?? [] );
	}

	public function pingWorker( ?Worker $worker ): void
	{
		$fs   = new Filesystem();
		$pid  = getmypid();
		$file = $this->getPingFilePath( $pid );

		/*if ( $this::$_registered_worker_pid !== $pid ) {
			$this->registerWorker( $worker );
		}*/

		if ( ! $fs->exists( $file ) ) {
			$fs->touch( $file );
		}

		$fs->dumpFile( $file, time() );
	}

	public function registerWorker( Worker $worker ): void
	{
		$transportNames = $worker->getMetadata()->getTransportNames();

		$workers = $this->fetch();
		$pid     = getmypid();

		// Reregister worker if it already exists.
		/*if ( isset( $workers['__pid'][ $pid ] ) ) {
			$this->unregisterWorker( null, $pid );
			$workers = $this->getWorkerRegistry();
		}*/

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
				$transportList[ $transport ] = [ $pid => $pid ];
			} else {
				$transportList[ $transport ][ $pid ] = $pid;
			}
		}

		$workers['__transports'] = $transportList;

		$this->store( $workers );

		$this::$_registered_pid = $pid;
	}

	public function unregisterWorker( ?Worker $worker, ?int $pid = null ): void
	{
		$workers = $this->fetch();

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

		( new Filesystem() )->remove( $this->getPingFilePath( $pid ) );

		$transportList = $workers['__transports'] ?? [];

		foreach ( $transportNames as $transport ) {
			if ( isset( $transportList[ $transport ] ) ) {
				unset( $transportList[ $transport ][ $pid ] );
			}
		}

		$workers['__transports'] = $transportList;

		$this->store( $workers );
	}
}
