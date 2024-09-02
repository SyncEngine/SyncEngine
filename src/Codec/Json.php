<?php

namespace SyncEngine\Codec;

use Symfony\Component\Serializer\Encoder\JsonDecode;
use SyncEngine\Model\CodecModel;
use SyncEngine\Service\Interface\CodecInterface;
use SyncEngine\Service\Serializer\JsonEncoder;

class Json extends CodecModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'serializer';
		$this->name        = 'JSON';
		$this->description = $this->trans( 'JavaScript Object Notation' );
	}

	/**
	 * @link https://symfony.com/doc/current/components/serializer.html#the-jsonencoder
	 *
	 * JsonDecode::ASSOCIATIVE => true,
	 */
	public function initEncoder( ?array $config = [] ): CodecInterface
	{
		$defaultContext = [];
		if ( $config ) {
			if ( isset( $config['associative'] ) ) {
				$defaultContext[ JsonDecode::ASSOCIATIVE ] = (bool) $config['associative'];
			}
		}

		return new JsonEncoder( defaultContext: $defaultContext );
	}

	public function getFormats(): array
	{
		return [ 'json' ];
	}

	public function getContentType( array $config = [], string $format = '' ): string
	{
		return 'application/json';
	}

	public function getCodecFields( $defaults = [] ): array
	{
		return [
			'associative' => [
				'label'      => 'Associative',
				'type'       => 'checkbox',
				'default'    => $defaults['associative'] ?? true,
				'_codec'     => 'decode',
			],
		];
	}
}
