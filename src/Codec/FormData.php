<?php

namespace SyncEngine\Codec;

use SyncEngine\Model\CodecModel;
use SyncEngine\Service\Interface\CodecInterface;
use SyncEngine\Service\Serializer\FormDataEncoder;

class FormData extends CodecModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'serializer';
		$this->name        = 'FormData';
		$this->description = $this->trans( 'FormData encoded string' );
	}

	public function initEncoder( ?array $config = [] ): CodecInterface
	{
		return new FormDataEncoder();
	}

	public function getFormats(): array
	{
		return [ 'formdata' ];
	}

	public function getContentType( array $config = [], string $format = '' ): string
	{
		return 'application/x-www-form-urlencoded';
		//return 'multipart/form-data';
	}

	public function getCodecFields( $defaults = [] ): array
	{
		return [];
	}
}
