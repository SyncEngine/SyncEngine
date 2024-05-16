<?php

namespace SyncEngine\Service;

use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use SyncEngine\Model\Trait\Format;
use SyncEngine\Service\Serializer\ExcelEncoder;
use SyncEngine\Service\Serializer\FormDataEncoder;
use SyncEngine\Service\Serializer\UrlEncoder;

class DataFormatter
{
	use Format;

	public function encode( string|array $format, array $data, array $config = [] ): array|string
	{
		$format = $this->getFormat( $format, $config );

		$encoder = $this->getEncoder( $format, $config );

		return ( $encoder ) ? $encoder->encode( $data, $format ) : $data;
	}

	public function decode( string|array $format, string $data, array $config = [] ): array|string
	{
		$format = $this->getFormat( $format, $config );

		$encoder = $this->getEncoder( $format, $config );

		return ( $encoder ) ? $encoder->decode( $data, $format ) : $data;
	}

	public function getFormat( string|array $format, array $config = [] ): string
	{
		if ( is_array( $format ) ) {
			$format = $format['format'] ?? '';
			$config = $format;
		}

		switch ( $format ) {
			case 'xls':
				$format = $config['xls_type'] ?? $format;
			break;
		}

		return $format;
	}

	public function getContentType( $format )
	{
		if ( is_array( $format ) ) {
			$format = $format['format'] ?? '';
		}

		$encoder = $this->getEncoder( $format );

		if ( $encoder && method_exists( $encoder, 'getContentType' ) ) {
			return $encoder->getContentType();
		}

		return match ( $format ) {
			'json'     => 'application/json',
			'csv'      => 'text/csv',
			'xml'      => 'application/xml',
			'yaml'     => 'text/yaml',
			'xls'      => 'application/vnd.ms-excel',
			'xlsx'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'formdata' => 'application/x-www-form-urlencoded',
			default => '',
		};
	}

	public function getEncoder( $format, $config = [] ): mixed
	{
		switch ( $format ) {
			case 'url':
				return new UrlEncoder();

			case 'formdata':
				return new FormDataEncoder();

			case 'json':
				/**
				 * @link https://symfony.com/doc/current/components/serializer.html#the-jsonencoder
				 *
				 * JsonDecode::ASSOCIATIVE => true,
				 */
				$defaultContext = [];
				if ( $config ) {
					if ( isset( $config['json_associative'] ) ) {
						$defaultContext[ JsonDecode::ASSOCIATIVE ] = (bool) $config['json_associative'];
					}
				}

				return new JsonEncoder( null, null, $defaultContext );

			case 'csv':
				/**
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
				$defaultContext = [];
				if ( $config ) {
					if ( isset( $config['csv_delimiter'] ) ) {
						$defaultContext[ CsvEncoder::DELIMITER_KEY ] = (string) $config['csv_delimiter'];
					}
					if ( isset( $config['csv_enclosure'] ) ) {
						$defaultContext[ CsvEncoder::ENCLOSURE_KEY ] = (string) $config['csv_enclosure'];
					}
					if ( isset( $config['csv_escape_char'] ) ) {
						$defaultContext[ CsvEncoder::ESCAPE_CHAR_KEY ] = (string) $config['csv_escape_char'];
					}
					if ( isset( $config['csv_end_of_line'] ) ) {
						$defaultContext[ CsvEncoder::END_OF_LINE ] = (string) $config['csv_end_of_line'];
					}
					if ( isset( $config['csv_escape_formulas'] ) ) {
						$defaultContext[ CsvEncoder::ESCAPE_FORMULAS_KEY ] = (bool) $config['csv_escape_formulas'];
					}
					if ( isset( $config['csv_headers'] ) ) {
						$defaultContext[ CsvEncoder::HEADERS_KEY ] = (array) $config['csv_headers'];
					}
					if ( isset( $config['csv_key_separator'] ) ) {
						$defaultContext[ CsvEncoder::KEY_SEPARATOR_KEY ] = (string) $config['csv_key_separator'];
					}
					if ( isset( $config['csv_no_headers'] ) ) {
						$defaultContext[ CsvEncoder::NO_HEADERS_KEY ] = (bool) $config['csv_no_headers'];
					}
					if ( isset( $config['csv_as_collection'] ) ) {
						$defaultContext[ CsvEncoder::AS_COLLECTION_KEY ] = (bool) $config['csv_as_collection'];
					}
					if ( isset( $config['csv_output_utf8_bom'] ) ) {
						$defaultContext[ CsvEncoder::OUTPUT_UTF8_BOM_KEY ] = (bool) $config['csv_output_utf8_bom'];
					}
				}

				return new CsvEncoder( $defaultContext );

			case 'xls':
			case 'xlsx':
				/**
				 * @link https://github.com/Ang3/php-excel-encoder
				 * @link https://github.com/PHPOffice/phpspreadsheet/
				 *
				 * self::AS_COLLECTION_KEY => true,
				 * self::FLATTENED_HEADERS_SEPARATOR_KEY => '.',
				 * self::HEADERS_IN_BOLD_KEY => true,
				 * self::HEADERS_HORIZONTAL_ALIGNMENT_KEY => 'center',
				 * self::COLUMNS_AUTOSIZE_KEY => true,
				 * self::COLUMNS_MAXSIZE_KEY => 50,
				 */
				$defaultContext = [];
				if ( $config ) {
					if ( isset( $config['xls_as_collection'] ) ) {
						$defaultContext[ ExcelEncoder::AS_COLLECTION_KEY ] = (bool) $config['xls_as_collection'];
					}
					if ( isset( $config['xls_flattened_headers_separator'] ) ) {
						$defaultContext[ ExcelEncoder::FLATTENED_HEADERS_SEPARATOR_KEY ] = (bool) $config['xls_flattened_headers_separator'];
					}
					if ( isset( $config['xls_headers_in_bold'] ) ) {
						$defaultContext[ ExcelEncoder::HEADERS_IN_BOLD_KEY ] = (bool) $config['xls_headers_in_bold'];
					}
					if ( isset( $config['xls_headers_horizontal_alignment'] ) ) {
						$defaultContext[ ExcelEncoder::HEADERS_HORIZONTAL_ALIGNMENT_KEY ] = (bool) $config['xls_headers_horizontal_alignment'];
					}
					if ( isset( $config['xls_columns_autosize'] ) ) {
						$defaultContext[ ExcelEncoder::COLUMNS_AUTOSIZE_KEY ] = (bool) $config['xls_columns_autosize'];
					}
					if ( isset( $config['xls_columns_maxsize'] ) ) {
						$defaultContext[ ExcelEncoder::COLUMNS_MAXSIZE_KEY ] = (bool) $config['xls_columns_maxsize'];
					}
					if ( isset( $config['xls_null_value'] ) ) {
						$defaultContext[ ExcelEncoder::NULL_VALUE ] = $config['xls_null_value'];
					}
					if ( isset( $config['xls_calculate_formulas'] ) ) {
						$defaultContext[ ExcelEncoder::CALCULATE_FORMULAS ] = (bool) $config['xls_calculate_formulas'];
					}
					if ( isset( $config['xls_format_data'] ) ) {
						$defaultContext[ ExcelEncoder::FORMAT_DATA ] = (bool) $config['xls_format_data'];
					}
					if ( isset( $config['xls_return_cell_ref'] ) ) {
						$defaultContext[ ExcelEncoder::RETURN_CELL_REF ] = (bool) $config['xls_return_cell_ref'];
					}
					if ( isset( $config['xls_ignore_hidden'] ) ) {
						$defaultContext[ ExcelEncoder::IGNORE_HIDDEN ] = (bool) $config['xls_ignore_hidden'];
					}
				}

				return new ExcelEncoder( $defaultContext );

			case 'xml':
				/**
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
				$defaultContext = [];
				if ( $config ) {
					if ( isset( $config['xml_format_output'] ) ) {
						$defaultContext[ XmlEncoder::FORMAT_OUTPUT ] = (bool) $config['xml_format_output'];
					}
					if ( isset( $config['xml_version'] ) ) {
						$defaultContext[ XmlEncoder::VERSION ] = (bool) $config['xml_version'];
					}
					if ( isset( $config['xml_encoding'] ) ) {
						$defaultContext[ XmlEncoder::ENCODING ] = (bool) $config['xml_encoding'];
					}
					if ( isset( $config['xml_standalone'] ) ) {
						$defaultContext[ XmlEncoder::STANDALONE ] = (bool) $config['xml_standalone'];
					}
					if ( isset( $config['xml_as_collection'] ) ) {
						$defaultContext[ XmlEncoder::AS_COLLECTION ] = (bool) $config['xml_as_collection'];
					}
					if ( isset( $config['xml_remove_empty_tags'] ) ) {
						$defaultContext[ XmlEncoder::REMOVE_EMPTY_TAGS ] = (bool) $config['xml_remove_empty_tags'];
					}
					if ( isset( $config['xml_root_node_name'] ) ) {
						$defaultContext[ XmlEncoder::ROOT_NODE_NAME ] = (string) $config['xml_root_node_name'];
					}
					if ( isset( $config['xml_type_cast_attributes'] ) ) {
						$defaultContext[ XmlEncoder::TYPE_CAST_ATTRIBUTES ] = (bool) $config['xml_type_cast_attributes'];
					}
				}

				return new XmlEncoder( $defaultContext );

			case 'yaml':
			case 'yml':
				/**
				 * @link https://symfony.com/doc/current/components/serializer.html#the-yamlencoder
				 *
				 * self::YAML_INLINE => 0,
				 * self::YAML_INDENT => 0,
				 * self::YAML_FLAGS => 0,
				 */
				$defaultContext = [];
				if ( $config ) {
					if ( isset( $config['yaml_inline'] ) ) {
						$defaultContext[ YamlEncoder::YAML_INLINE ] = 1;
					}
					if ( isset( $config['yaml_indent'] ) ) {
						$defaultContext[ YamlEncoder::YAML_INDENT ] = (int) $config['yaml_indent'];
					}
				}

				return new YamlEncoder( null, null, $defaultContext );
		}

		return null;
	}
}
