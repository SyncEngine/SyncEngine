<?php

namespace SyncEngine\Codec;

use SyncEngine\Model\CodecModel;
use SyncEngine\Service\Interface\CodecInterface;
use SyncEngine\Service\Serializer\UrlEncoder;

class Url extends CodecModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'serializer';
		$this->name        = 'URL';
		$this->description = $this->trans( 'URL encoded string' );
	}

	public function initEncoder( ?array $config = [] ): CodecInterface
	{
		return new UrlEncoder();
	}

	public function getFormats(): array
	{
		return [ 'url' ];
	}

	public function getContentType( array $config = [], string $format = '' ): string
	{
		return 'application/x-www-form-urlencoded';
	}

	public function getCodecFields( $defaults = [] ): array
	{
		return [];
	}
}
