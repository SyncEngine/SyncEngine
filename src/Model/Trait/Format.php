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
				$defaultContext = [
					/*
			        self::DELIMITER_KEY => ',',
			        self::ENCLOSURE_KEY => '"',
			        self::ESCAPE_CHAR_KEY => '',
			        self::END_OF_LINE => "\n",
			        self::ESCAPE_FORMULAS_KEY => false,
			        self::HEADERS_KEY => [],
			        self::KEY_SEPARATOR_KEY => '.',
			        self::NO_HEADERS_KEY => false,
			        self::AS_COLLECTION_KEY => true,
			        self::OUTPUT_UTF8_BOM_KEY => false,
					*/
				];
				if ( $config ) {
					if ( ! empty( $config['csv_delimiter_key'] ) ) {
						$defaultContext[ CsvEncoder::DELIMITER_KEY ] = (string) $config['csv_delimiter_key'];
					}
					if ( ! empty( $config['csv_enclosure_key'] ) ) {
						$defaultContext[ CsvEncoder::ENCLOSURE_KEY ] = (string) $config['csv_enclosure_key'];
					}
					if ( ! empty( $config['csv_escape_char_key'] ) ) {
						$defaultContext[ CsvEncoder::ESCAPE_CHAR_KEY ] = (string) $config['csv_escape_char_key'];
					}
					if ( ! empty( $config['csv_end_of_line'] ) ) {
						$defaultContext[ CsvEncoder::END_OF_LINE ] = (string) $config['csv_end_of_line'];
					}
					if ( ! empty( $config['csv_escape_formulas_key'] ) ) {
						$defaultContext[ CsvEncoder::ESCAPE_FORMULAS_KEY ] = (bool) $config['csv_escape_formulas_key'];
					}
					if ( ! empty( $config['csv_headers_key'] ) ) {
						$defaultContext[ CsvEncoder::HEADERS_KEY ] = (array) $config['csv_headers_key'];
					}
					if ( ! empty( $config['csv_key_separator_key'] ) ) {
						$defaultContext[ CsvEncoder::KEY_SEPARATOR_KEY ] = (string) $config['csv_key_separator_key'];
					}
					if ( ! empty( $config['csv_no_headers_key'] ) ) {
						$defaultContext[ CsvEncoder::NO_HEADERS_KEY ] = (bool) $config['csv_no_headers_key'];
					}
					if ( ! empty( $config['csv_as_collection_key'] ) ) {
						$defaultContext[ CsvEncoder::AS_COLLECTION_KEY ] = (bool) $config['csv_as_collection_key'];
					}
					if ( ! empty( $config['csv_output_utf8_bom_key'] ) ) {
						$defaultContext[ CsvEncoder::OUTPUT_UTF8_BOM_KEY ] = (bool) $config['csv_output_utf8_bom_key'];
					}
				}

				return new CsvEncoder( $defaultContext );

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

		return $fields;
	}

	public function getFormatCsvFields(): array
	{
		return [
			'csv_delimiter_key' => [
				'label' => 'csv_delimiter_key',
				'type' => 'text',
			],
			'csv_enclosure_key' => [
				'label' => 'csv_enclosure_key',
				'type' => 'text',
			],
			'csv_escape_char_key' => [
				'label' => 'csv_escape_char_key',
				'type' => 'text',
			],
			'csv_end_of_line'  => [
				'label' => 'csv_end_of_line',
				'type' => 'text',
			],
			'csv_escape_formula' => [
				'label' => 'csv_escape_formula',
				'type' => 'checkbox',
			],
			'csv_headers_key' => [
				'label' => 'csv_headers_key',
				'type' => 'text',
				'multiple' => true,
			],
			'csv_key_separator_key' => [
				'label' => 'csv_key_separator_key',
				'type' => 'text',
			],
			'csv_no_headers_key' => [
				'label' => 'csv_no_headers_key',
				'type' => 'checkbox',
			],
			'csv_as_collection_key' => [
				'label' => 'csv_as_collection_key',
				'type' => 'checkbox',
				'default' => true,
			],
			'csv_output_utf8_bom_key' => [
				'label' => 'csv_output_utf8_bom_key',
				'type' => 'checkbox',
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
