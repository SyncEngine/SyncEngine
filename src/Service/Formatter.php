<?php

namespace App\Service;

use App\Model\Trait\Format;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;

class Formatter
{
	use Format;

	public function encode( string|array $format, array $data, array $config = [] ): array|string
	{
		$encoder = $this->getEncoder( $format, $config );

		return ( $encoder ) ? $encoder->encode( $data, $format ) : $data;
	}

	public function decode( string|array $format, string $data, array $config = [] ): array|string
	{
		$encoder = $this->getEncoder( $format, $config );

		return ( $encoder ) ? $encoder->decode( $data, $format ) : $data;
	}

	public function getEncoder( $format, $config = [] ): mixed
	{
		if ( is_array( $format ) ) {
			$format = $format['format'] ?? '';
			$config = $format;
		}

		switch ( $format ) {
			case 'url':
				// @todo Convert to actual class?
				return new class {
					public function encode( $data )
					{
						return http_build_query( $data );
					}

					public function decode( $string )
					{
						parse_str( $string, $parsed );

						return $parsed;
					}
				};

			case 'json':
				$defaultContext = [/*
					 * @link https://symfony.com/doc/current/components/serializer.html#the-jsonencoder
					 *
			         * JsonDecode::ASSOCIATIVE => true,
					*/
				];
				if ( $config ) {
					if ( ! empty( $config['json_associative'] ) ) {
						$defaultContext[ JsonDecode::ASSOCIATIVE ] = (bool) $config['json_associative'];
					}
				}

				return new JsonEncoder( null, null, $defaultContext );

			case 'csv':
				$defaultContext = [/*
					 * @link https://symfony.com/doc/current/components/serializer.html#the-csvencoder
					 *
			         * self::DELIMITER_KEY => ',',
			         * self::ENCLOSURE_KEY => '"',
			         * self::ESCAPE_CHAR_KEY => '',
			         * self::END_OF_LINE => "\n",
			         * self::ESCAPE_FORMULAS_KEY => false,
			         * self::HEADERS_KEY => [],
			         * self::KEY_SEPARATOR_KEY => '.',
			         * self::NO_HEADERS_KEY => false,
			         * self::AS_COLLECTION_KEY => true,
			         * self::OUTPUT_UTF8_BOM_KEY => false,
					*/
				];
				if ( $config ) {
					if ( ! empty( $config['csv_delimiter'] ) ) {
						$defaultContext[ CsvEncoder::DELIMITER_KEY ] = (string) $config['csv_delimiter'];
					}
					if ( ! empty( $config['csv_enclosure'] ) ) {
						$defaultContext[ CsvEncoder::ENCLOSURE_KEY ] = (string) $config['csv_enclosure'];
					}
					if ( ! empty( $config['csv_escape_char'] ) ) {
						$defaultContext[ CsvEncoder::ESCAPE_CHAR_KEY ] = (string) $config['csv_escape_char'];
					}
					if ( ! empty( $config['csv_end_of_line'] ) ) {
						$defaultContext[ CsvEncoder::END_OF_LINE ] = (string) $config['csv_end_of_line'];
					}
					if ( ! empty( $config['csv_escape_formulas'] ) ) {
						$defaultContext[ CsvEncoder::ESCAPE_FORMULAS_KEY ] = (bool) $config['csv_escape_formulas'];
					}
					if ( ! empty( $config['csv_headers'] ) ) {
						$defaultContext[ CsvEncoder::HEADERS_KEY ] = (array) $config['csv_headers'];
					}
					if ( ! empty( $config['csv_key_separator'] ) ) {
						$defaultContext[ CsvEncoder::KEY_SEPARATOR_KEY ] = (string) $config['csv_key_separator'];
					}
					if ( ! empty( $config['csv_no_headers'] ) ) {
						$defaultContext[ CsvEncoder::NO_HEADERS_KEY ] = (bool) $config['csv_no_headers'];
					}
					if ( ! empty( $config['csv_as_collection'] ) ) {
						$defaultContext[ CsvEncoder::AS_COLLECTION_KEY ] = (bool) $config['csv_as_collection'];
					}
					if ( ! empty( $config['csv_output_utf8_bom'] ) ) {
						$defaultContext[ CsvEncoder::OUTPUT_UTF8_BOM_KEY ] = (bool) $config['csv_output_utf8_bom'];
					}
				}

				return new CsvEncoder( $defaultContext );

			case 'xml':
				$defaultContext = [/*
					 * @link https://symfony.com/doc/current/components/serializer.html#the-xmlencoder
					 *
					 * self::AS_COLLECTION => false,
					 * self::DECODER_IGNORED_NODE_TYPES => [\XML_PI_NODE, \XML_COMMENT_NODE],
					 * self::ENCODER_IGNORED_NODE_TYPES => [],
					 * self::LOAD_OPTIONS => \LIBXML_NONET | \LIBXML_NOBLANKS,
					 * self::REMOVE_EMPTY_TAGS => false,
					 * self::ROOT_NODE_NAME => 'response',
					 * self::TYPE_CAST_ATTRIBUTES => true,
					*/
				];
				if ( $config ) {
					if ( ! empty( $config['xml_format_output'] ) ) {
						$defaultContext[ XmlEncoder::FORMAT_OUTPUT ] = (bool) $config['xml_format_output'];
					}
					if ( ! empty( $config['xml_version'] ) ) {
						$defaultContext[ XmlEncoder::VERSION ] = (bool) $config['xml_version'];
					}
					if ( ! empty( $config['xml_encoding'] ) ) {
						$defaultContext[ XmlEncoder::ENCODING ] = (bool) $config['xml_encoding'];
					}
					if ( ! empty( $config['xml_standalone'] ) ) {
						$defaultContext[ XmlEncoder::STANDALONE ] = (bool) $config['xml_standalone'];
					}
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
				$defaultContext = [/*
					 * @link https://symfony.com/doc/current/components/serializer.html#the-yamlencoder
					 *
			         * self::YAML_INLINE => 0,
			         * self::YAML_INDENT => 0,
			         * self::YAML_FLAGS => 0,
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
}
