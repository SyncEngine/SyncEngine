<?php

namespace SyncEngine\Service;

use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Model\TraceModel;

class ExecuteLocalBatch
{
	private array $batches;

	protected function __construct(
		private readonly TraceModel $trace,
		?ExecuteData $data = null,
		?int $length = null,
		bool $store = true
	) {
		if ( $data ) {
			$this->batches = $data->chunk( $length, true );

			if ( $store ) {
				foreach ( $this->batches as $index => $batch ) {
					$this->storeBatch( $batch, ++$index );
				}
			}
		}
	}

	/**
	 * @param  TraceModel   $trace
	 * @param  ExecuteData  $data    The full data.
	 * @param  int          $length  The batch chunk length.
	 * @param  bool         $store   Whether to store the batches in the filesystem.
	 *
	 * @return ExecuteLocalBatch
	 */
	public static function create(
		TraceModel $trace,
		ExecuteData $data,
		int $length,
		bool $store = true
	): ExecuteLocalBatch
	{
		return new static( $trace, $data, $length, $store );
	}

	public static function load( TraceModel $trace ): ExecuteLocalBatch
	{
		return new static( $trace );
	}

	public function getBatch( int $iteration = 1 ): ?ExecuteData
	{
		$iteration = $iteration ?: 1;
		$index = $iteration--;

		if ( ! isset( $this->batches ) || empty( $this->batches[ $index ] ) ) {
			$batch = $this->fetchBatch( $iteration );
		} else {
			$batch = $this->batches[ $index ] ?? null;
		}

		if ( null === $batch ) {
			return null;
		}

		return ExecuteData::create( $batch );
	}

	public function fetchBatch( int $iteration ): ?array
	{
		$id = $this->trace->getId();
		if ( ! $id ) {
			return null;
		}

		$dir = $this->getBatchDir();
		$name = $this->getBatchFilename( $iteration );

		$file = $dir . $name;

		if ( ! $name || ! file_exists( $file ) ) {
			return null;
		}

		return json_decode( file_get_contents( $file ), true );
	}

	public function storeBatch( ExecuteData $data, int $iteration ): void
	{
		$id = $this->trace->getId();
		if ( ! $id ) {
			return;
		}

		$dir  = $this->getBatchDir();
		$name = $this->getBatchFilename( $iteration );

		if ( ! $name ) {
			return;
		}

		$file = $dir . $name;

		( new Filesystem() )->dumpFile( $file, json_encode( $data->get() ) );
	}

	public function getBatchFilename( $iteration ): string
	{
		return $this->getBatchIdentifier( $iteration ) . '.json';
	}

	public function getBatchIdentifier( $iteration ): string
	{
		return $this->trace->getTraceIdentifier( $iteration ) . '_batch';
	}

	public function getBatchDir(): string
	{
		$fs = new Filesystem();
		$dir = $this->trace->getTraceDir() . 'batches';

		if ( ! $fs->exists( $dir ) ) {
			$fs->mkdir( $dir );
		}

		return $dir . '/';
	}
}
