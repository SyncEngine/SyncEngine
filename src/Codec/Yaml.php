<?php

namespace SyncEngine\Codec;

use Symfony\Component\Serializer\Encoder\YamlEncoder as BaseYamlEncoder;
use SyncEngine\Model\CodecModel;
use SyncEngine\Service\Interface\CodecInterface;
use SyncEngine\Service\Serializer\YamlEncoder;

class Yaml extends CodecModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'serializer';
		$this->name        = 'YAML';
		$this->description = $this->trans( 'YAML Ain\'t Markup Language' );
	}

	/**
	 * @link https://symfony.com/doc/current/components/serializer.html#the-yamlencoder
	 *
	 * self::YAML_INLINE => 0,
	 * self::YAML_INDENT => 0,
	 * self::YAML_FLAGS => 0,
	 */
	public function initEncoder( ?array $config = [] ): CodecInterface
	{
		$defaultContext = [];
		if ( $config ) {
			if ( isset( $config['inline'] ) ) {
				$defaultContext[ BaseYamlEncoder::YAML_INLINE ] = (int) (bool) $config['inline'];
			}
			if ( isset( $config['indent'] ) ) {
				$defaultContext[ BaseYamlEncoder::YAML_INDENT ] = (int) $config['indent'];
			}
		}

		return new YamlEncoder( defaultContext: $defaultContext );
	}

	public function getFormats(): array
	{
		return [ 'yaml', 'yml' ];
	}

	public function getContentType( array $config = [], string $format = '' ): string
	{
		return 'text/yaml';
	}

	public function getCodecFields( $defaults = [] ): array
	{
		return [
			'inline' => [
				'label'      => 'Inline dump',
				'type'       => 'checkbox',
				'default'    => $defaults['inline'] ?? null,
				'_direction' => 'encode',
			],
			'indent' => [
				'label'       => 'Indentation',
				'placeholder' => 'Number of spaces',
				'type'        => 'number',
				'default'     => $defaults['indent'] ?? null,
				'_direction'  => 'encode',
			],
		];
	}
}
