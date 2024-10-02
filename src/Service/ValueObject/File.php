<?php

namespace SyncEngine\Service\ValueObject;

use SyncEngine\Service\ModelNormalizer;

class File
{
	private string $name;
	private string $extension;
	private string $mimeType;
	private int $size;
	private string $base64;
	private string $url;

	/**
	 * @param  string  $name
	 * @param  string  $extension
	 * @param  string  $mimeType
	 * @param  int     $size
	 * @param  string  $base64
	 * @param  string  $url
	 */
	public function __construct(
		string $name = '',
		string $extension = '',
		string $mimeType = '',
		int $size = 0,
		string $base64 = '',
		string $url = '',
	) {
		$this->name      = $name;
		$this->extension = $extension;
		$this->size      = $size;
		$this->mimeType  = $mimeType;
		$this->url       = $url;
		$this->base64    = $base64;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName( string $name ): File
	{
		$new = clone $this;
		$new->name = $name;

		return $new;
	}

	public function getExtension(): string
	{
		return $this->extension;
	}

	public function setExtension( string $extension ): File
	{
		$new = clone $this;
		$new->extension = $extension;

		return $new;
	}

	public function getSize(): int
	{
		return $this->size;
	}

	public function setSize( int $size ): File
	{
		$new = clone $this;
		$new->size = $size;

		return $new;
	}

	public function getMimeType(): string
	{
		return $this->mimeType;
	}

	public function setMimeType( string $mimeType ): File
	{
		$new = clone $this;
		$new->mimeType = $mimeType;

		return $new;
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function setUrl( string $url ): File
	{
		$new = clone $this;
		$new->url = $url;

		return $new;
	}

	public function getBase64(): string
	{
		return $this->base64;
	}

	public function setBase64( string $base64 ): File
	{
		$new = clone $this;
		$new->base64 = $base64;

		return $new;
	}

	public function normalize(): array
	{
		return ( new ModelNormalizer() )->normalize( $this );
	}
}
