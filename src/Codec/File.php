<?php

namespace SyncEngine\Codec;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\CodecModel;
use SyncEngine\Service\Interface\CodecInterface;
use SyncEngine\Structure\ValueObject\Blob;

class File extends CodecModel
{
	public function __construct()
	{
		$this->type        = 'file';
		$this->name        = 'File';
		$this->description = 'Handle binary file data (raw bytes)';

		parent::__construct();
	}

	public function getFormats(): array
	{
		return [ 'file' ];
	}

	public function getContentType( array $config = [], string $format = '' ): string
	{
		return $config['mimeType'] ?? 'application/octet-stream';
	}

	public function initEncoder( array $config = [] ): ?CodecInterface
	{
		return null;
	}

	public function getCodecFields( array $defaults = [] ): FieldCollection
	{
		return new FieldCollection();
	}

	public function encode( $value, ?string $format = null, ?array $config = [] ): string|iterable
	{
		if ( $value instanceof Blob ) {
			return $value->getContents();
		}

		return $value;
	}

	public function decode( string $value, ?string $format = null, ?array $config = [] ): Blob
	{
		$filename  = $config['filename'] ?? $this->getConfig( 'filename' ) ?? 'downloaded';
		$extension = $config['extension'] ?? $this->getConfig( 'extension' ) ?? '';
		$mimeType  = $config['mimeType'] ?? $this->getConfig( 'mimeType' ) ?? null;

		return new Blob( $value, $filename, $extension, $mimeType );
	}

	public function getFields( array $defaults = [], array $filters = [] ): FieldCollection
	{
		return new FieldCollection( [
			'filename'  => [
				'label'       => 'Filename',
				'type'        => 'text',
				'default'     => $defaults['filename'] ?? '',
				'placeholder' => 'e.g. my-file.txt',
				'_direction'  => 'decode',
			],
			'extension' => [
				'label'       => 'Extension',
				'type'        => 'text',
				'default'     => $defaults['extension'] ?? '',
				'placeholder' => 'e.g. txt, pdf, jpg, etc.',
				'_direction'  => 'decode',
			],
			'mimeType'  => [
				'label'       => 'Mime type',
				'type'        => 'text',
				'default'     => $defaults['mimeType'] ?? '',
				'placeholder' => 'e.g. text/plain, application/pdf, image/jpeg, etc.',
				'_direction'  => 'decode',
			],
		] );
	}
}
