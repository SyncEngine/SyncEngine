<?php

namespace SyncEngine\Service;

use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Structure\ValueObject\Blob;

/**
 * Manages temporary file storage for Blob ValueObjects within a trace or temp scope.
 *
 * Files are copied from SplTempFileObject streams to a persistent directory on disk,
 * identified by a unique UUID. Blobs can be rehydrated back into SplTempFileObject
 * instances via their ID.
 *
 * Note: The static instance is intentionally process-scoped. Each trace may run in
 * a separate process (CLI commands, Messenger workers). Do not rely on the singleton
 * persisting across process boundaries. New code should prefer DI injection.
 */
class BlobStore
{
	private string $directory;

	/** @var BlobStore|null Active runtime store instance (for execution contexts) */
	private static ?BlobStore $runtimeInstance = null;

	public function __construct( string $directory )
	{
		$this->directory = rtrim( $directory, '/' ) . '/blobs/';

		( new Filesystem() )->mkdir( $this->directory );
	}

	public static function getInstance( string $directory = null ): self
	{
		if ( $directory !== null ) {
			return new self( $directory );
		}

		return self::getRuntimeInstance() ?? new self();
	}

	/**
	 * Set the active BlobStore instance for execution contexts.
	 */
	public static function setRuntimeInstance( ?self $store ): void
	{
		self::$runtimeInstance = $store;
	}

	/**
	 * Get the active BlobStore instance.
	 */
	public static function getRuntimeInstance(): ?self
	{
		return self::$runtimeInstance;
	}

	/**
	 * Rehydrate a Blob from its normalized marker array.
	 * Always reads from disk, no identity map to avoid memory bloat with hundreds of files.
	 */
	public function rehydrate( array $marker ): ?Blob
	{
		if ( ! isset( $marker['_ref'] ) || ! is_string( $marker['_ref'] ) ) {
			return null;
		}

		$ref = $marker['_ref'];

		// Read from disk.
		$stream = $this->get( $ref );
		if ( ! $stream instanceof \SplFileObject ) {
			return null;
		}

		// Create a new Blob from the rehydrated stream.
		return Blob::fromRef(
			$ref,
			$stream,
			$marker['filename'] ?? 'file',
			$marker['extension'] ?? '',
			$marker['mimeType'] ?? null
		);
	}

	/**
	 * Register a Blob by copying its stream to disk. Returns the unique storage ref.
	 *
	 * Reads the blob in 8KB chunks to avoid loading the full content into PHP memory.
	 *
	 * @todo Optionally handle content hash deduplication, compute a hash of the blob content,
	 *       store ref→hash map, and check if any existing blob has the same hash before
	 *       re-registering. This handles the "same content, different refs" case where
	 *       checking the ref alone won't help.
	 */
	public function register( Blob $blob ): string
	{
		// Sets ref in Blob.
		$ref = $blob->getRef();

		$target = $this->directory . $ref;

		// Skip if already exists.
		if ( file_exists( $target ) ) {
			return $ref;
		}

		$stream = $blob->getStream();
		$stream->rewind();
		$targetFile = fopen( $target, 'wb' );

		while ( ! $stream->eof() ) {
			$chunk = $stream->fread( 8192 );
			if ( $chunk === false ) {
				break;
			}
			fwrite( $targetFile, $chunk );
		}

		fclose( $targetFile );

		return $ref;
	}

	/**
	 * Fetch a File from its stored Blob ref. Returns null if the file no longer exists.
	 */
	public function get( string $ref ): ?\SplFileObject
	{
		$path = $this->directory . $ref;

		if ( ! is_file( $path ) ) {
			return null;
		}

		return new \SplFileObject( $path, 'rb' );
	}

	/**
	 * Get the filesystem path for a stored blob (for BinaryFileResponse consumers).
	 */
	public function getPath( string $ref ): ?string
	{
		$path = $this->directory . $ref;

		return file_exists( $path ) ? $path : null;
	}

	/**
	 * Delete a stored blob by ref.
	 */
	public function delete( string $ref ): void
	{
		$path = $this->directory . $ref;
		if ( file_exists( $path ) ) {
			unlink( $path );
		}
	}

	/**
	 * Check if a stored blob exists by ref.
	 */
	public function exists( string $ref ): bool
	{
		return file_exists( $this->directory . $ref );
	}

	/**
	 * Get the base directory path (useful for cleanup).
	 */
	public function getDirectory(): string
	{
		return $this->directory;
	}
}
