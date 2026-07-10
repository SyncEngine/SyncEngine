<?php

namespace SyncEngine\Structure\ValueObject;

use SyncEngine\Exception\InvalidValueException;
use SyncEngine\Model\Trait\Ref;

/**
 * ValueObject representing a file blob within the automation dataflow.
 *
 * Acts as a placeholder for files — wraps an SplTempFileObject with metadata
 * (filename, extension, mimeType). When placed in ExecuteData/ResourceData,
 * it normalizes to a small marker array so the dataflow doesn't carry raw bytes.
 */
class Blob
{
	use Ref;

	/** @var \SplFileObject|null Cached stream for inline blobs (avoids recreating SplTempFileObject) */
	private ?\SplFileObject $stream = null;

	public function __construct(
		private \SplFileObject|string $data,
		private string                $filename,
		private ?string               $extension = '',
		private ?string               $mimeType = null,
	) {}

	/**
	 * Create a Blob from a stored ref (rehydration factory).
	 * The actual file data is not loaded until getData() is called.
	 */
	public static function fromRef( string $ref, \SplFileObject|string $data, string $filename, ?string $extension = '', ?string $mimeType = null ): self
	{
		$blob      = new self( $data, $filename, $extension, $mimeType );
		$blob->ref = $ref;

		return $blob;
	}

	public static function fromFile( \SplFileInfo $file): self
	{
		try {
			$filename  = $file->getFilename();
			$extension = $file->getExtension();
			$mimeType  = $file->getMimeType();

			$data = $file->openFile();
		} catch ( \Exception $e ) {
			throw new InvalidValueException( $e->getMessage(), $e->getCode(), $e );
		}

		return new self(
			data: $data,
			filename: $filename,
			extension: $extension,
			mimeType: $mimeType
		);
	}

	public function getRef(): string {
		if ( empty( $this->ref ) ) {
			$this->createRef( true );
		}

		return $this->ref;
	}

	/**
	 * Get the full contents of this blob as a string.
	 *
	 * For inline (string-backed) blobs this returns the string directly — zero allocation.
	 * For stored (file-backed) blobs this loads the entire file into memory.
	 *
	 * Use getStream() for large files to avoid loading the full content into PHP memory.
	 */
	public function getContents(): string
	{
		if ( is_string( $this->data ) ) {
			return $this->data;
		}

		return $this->getSplFile()->fread( $this->getSize() );
	}

	/**
	 * Get a seekable SplFileObject stream for this blob's data.
	 *
	 * For inline (string-backed) blobs this creates and caches a SplTempFileObject on first call.
	 * For stored (file-backed) blobs this returns a new SplFileObject pointing to the disk file.
	 *
	 * The stream is always rewound to position 0 before returning. Callers should rewind()
	 * themselves between reads since we don't know when the stream will be exhausted.
	 *
	 * @todo For very large inline blobs, consider using a temp file on disk instead of SplTempFileObject
	 */
	public function getStream(): \SplFileObject
	{
		if ( is_string( $this->data ) ) {
			if ( ! $this->stream instanceof \SplFileObject ) {
				$temp = new \SplTempFileObject();
				$temp->fwrite( $this->data );
				$temp->rewind();
				$this->stream = $temp;
			}

			$this->stream->rewind();
			return $this->stream;
		}

		$file = new \SplFileObject( $this->data->getFileName(), 'rb' );
		$file->rewind();
		return $file;
	}

	public function getSplFile(): \SplFileObject
	{
		if ( is_string( $this->data ) ) {
			$data = new \SplTempFileObject();
			$data->fwrite( $this->data );
			$data->rewind();
			return $data;
		}

		return $this->data;
	}

	public function getSplFileInfo(): \SplFileInfo
	{
		return new \SplFileInfo( $this->getSplFile()->getFileName() );
	}

	public function getFilename(): string
	{
		if ( ! $this->extension ) {
			return $this->filename;
		}

		$ext = '.' . ltrim( $this->extension, '.' );
		if ( str_ends_with( $this->filename, $ext ) ) {
			return $this->filename;
		}

		return $this->filename . $ext;
	}

	public function getExtension(): string
	{
		return $this->extension;
	}

	public function getMimeType(): ?string
	{
		return $this->mimeType;
	}

	public function getSize(): int
	{
		if ( is_string( $this->data ) ) {
			return strlen( $this->data );
		}

		return $this->data->getSize();
	}

	/**
	 * Normalize to a serializable marker array for dataflow transport.
	 */
	public function normalize(): array
	{
		return [
			'_blob'     => true,
			'_ref'       => $this->getRef(),
			'filename'  => $this->getFilename(),
			'extension' => $this->getExtension(),
			'mimeType'  => $this->getMimeType(),
			'size'      => $this->getSize(),
		];
	}

	/**
	 * Check if this Blob is backed by a stored ref (vs. inline data).
	 */
	public function isStored(): bool
	{
		return ! empty( $this->ref );
	}
}
