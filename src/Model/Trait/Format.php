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
				$defaultContext = [
					/*
					self::AS_COLLECTION => false,
					self::DECODER_IGNORED_NODE_TYPES => [\XML_PI_NODE, \XML_COMMENT_NODE],
					self::ENCODER_IGNORED_NODE_TYPES => [],
					self::LOAD_OPTIONS => \LIBXML_NONET | \LIBXML_NOBLANKS,
					self::REMOVE_EMPTY_TAGS => false,
					self::ROOT_NODE_NAME => 'response',
					self::TYPE_CAST_ATTRIBUTES => true,
					*/
				];
				if ( $config ) {
					if ( ! empty( $config['xml_as_collection'] ) ) {
						$defaultContext[ XmlEncoder::AS_COLLECTION ] = (bool) $config['xml_as_collection'];
					}
					if ( ! empty( $config['xml_remove_empty_tags'] ) ) {
						$defaultContext[ XmlEncoder::REMOVE_EMPTY_TAGS ] = (bool) $config['xml_remove_empty_tags'];
					}
					if ( ! empty( $config['xml_root_node_name'] ) ) {
						$defaultContext[ XmlEncoder::ROOT_NODE_NAME ] = (string) $config['xml_root_node_name'];
					}
					if ( ! empty( $config['xml_type_cast_attributes'] ) ) {
						$defaultContext[ XmlEncoder::TYPE_CAST_ATTRIBUTES ] = (bool) $config['xml_type_cast_attributes'];
					}
				}
				return new XmlEncoder( $defaultContext );
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

	public function getFormatXmlFields(): array
	{
		return [
			'xml_as_collection' => [
				'label' => 'As collection',
				'type' => 'checkbox',
				'conditionals' => [ 'format' => 'xml' ],
			],
			'xml_remove_empty_tags' => [
				'label' => 'Remove empty tags',
				'type' => 'checkbox',
				'conditionals' => [ 'format' => 'xml' ],
			],
			'xml_root_node_name' => [
				'label' => 'Root node name',
				'placeholder' => 'response',
				'type' => 'checkbox',
				'conditionals' => [ 'format' => 'xml' ],
			],
			'xml_type_cast_attributes' => [
				'label' => 'Type-cast attributes',
				'type' => 'checkbox',
				'conditionals' => [ 'format' => 'xml' ],
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
