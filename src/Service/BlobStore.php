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

	/**
	 * Registry of ref → path, keyed by directory to avoid cross-store collisions.
	 */
	private static array $registry = [];

	public function __construct( ?string $directory = null )
	{
		if ( ! $directory ) {
			$this->directory = sys_get_temp_dir() . '/syncengine/blobs/';
		} else {
			$this->directory = rtrim( $directory, '/' ) . '/blobs/';
		}

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

		// Skip if file already exists on disk.
		if ( file_exists( $target ) ) {
			self::$registry[ $this->directory ][ $ref ] = $target;
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

		self::$registry[ $this->directory ][ $ref ] = $target;

		return $ref;
	}

	/**
	 * Fetch a File from its stored Blob ref. Returns null if the file no longer exists.
	 */
	public function get( string $ref ): ?\SplFileObject
	{
		$path = $this->getPath( $ref );

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
		// Check registry for this directory first.
		if ( isset( self::$registry[ $this->directory ][ $ref ] ) ) {
			$path = self::$registry[ $this->directory ][ $ref ];
			return file_exists( $path ) ? $path : null;
		}

		// Fallback to instance directory.
		$path = $this->directory . $ref;
		return file_exists( $path ) ? $path : null;
	}

	/**
	 * Delete a stored blob by ref.
	 */
	public function delete( string $ref ): void
	{
		$path = $this->getPath( $ref );

		if ( $path !== null && file_exists( $path ) ) {
			unlink( $path );
		}

		unset( self::$registry[ $this->directory ][ $ref ] );
	}

	/**
	 * Check if a stored blob exists by ref.
	 */
	public function exists( string $ref ): bool
	{
		return null !== $this->getPath( $ref );
	}

	/**
	 * Get the base directory path (useful for cleanup).
	 */
	public function getDirectory(): string
	{
		return $this->directory;
	}

	/**
	 * Clean up all stored blobs for this store's directory.
	 *
	 * Deletes all files and removes registry entries.
	 * Not called automatically, in runtime context cleanup is handled by the trace lifecycle.
	 */
	public function cleanup(): void
	{
		$dir = $this->directory;

		if ( isset( self::$registry[ $dir ] ) ) {
			foreach ( self::$registry[ $dir ] as $path ) {
				if ( file_exists( $path ) ) {
					unlink( $path );
				}
			}

			unset( self::$registry[ $dir ] );
		}

		// Remove empty directory.
		if ( is_dir( $dir ) ) {
			( new Filesystem() )->remove( $dir );
		}
	}

	/**
	 * Clear all registry entries across all directories.
	 *
	 * Does not delete files, use cleanup() on each store instance for that.
	 */
	public static function clearRegistry(): void
	{
		self::$registry = [];
	}
}
