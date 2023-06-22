<?php

namespace App\Model\Trait;

use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;

trait Format
{
	public function getFormatEncoder( $format, $config = [] )
	{
		switch ( $format ) {
			case 'json':
				return new JsonEncoder();
			case 'csv':
				return new CsvEncoder();
			case 'xml':
				return new XmlEncoder();
			case 'yaml':
				$defaultContext = [
					/*
			        self::YAML_INLINE => 0,
			        self::YAML_INDENT => 0,
			        self::YAML_FLAGS => 0,
					 */
				];
				if ( $config ) {
					if ( ! empty( $config['yaml_inline'] ) ) {
						$defaultContext[ YamlEncoder::YAML_INLINE ] = 1;
					}
					if ( ! empty( $config['yaml_indent'] ) ) {
						$defaultContext[ YamlEncoder::YAML_INDENT ] = (int) $config['yaml_indent'];
					}
				}
				return new YamlEncoder( null, null, $defaultContext );
		}
		return null;
	}

	public function getFormatFields(): array
	{
		$fields = [
			'format' => [
				'label' => 'Format',
				'type'  => 'select',
				'choices' => [
					'json' => 'JSON',
					'csv'  => 'CSV',
					'xml'  => 'XML',
					'yaml' => 'YAML',
				],
			],
		];
	}

	public function getFormatYamlFields(): array
	{
		return [
			'yaml_inline' => [
				'label' => 'Inline dump',
				'type' => 'checkbox',
				'conditionals' => [ 'format' => 'yaml' ],
			],
			'yaml_indent' => [
				'label' => 'Indentation',
				'placeholder' => 'Number of spaces',
				'type' => 'number',
				'conditionals' => [ 'format' => 'yaml' ],
			],
		];
	}

	public function toFormat( string $format, array $data, array $config = [] ): string
	{
		return $this->getFormatEncoder( $format, $config )->encode( $data, $format );
	}

	public function fromFormat( string $format, string $data, array $config = [] ): array
	{
		return $this->getFormatEncoder( $format, $config )->decode( $data, $format );
	}
}
