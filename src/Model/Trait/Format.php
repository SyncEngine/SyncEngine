<?php

namespace App\Model\Trait;

use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;

trait Format
{
	use Fields;

	public function getFormats( $overrides = [] ) {
		$default = [
			''     => 'Plain',
			'url'  => 'URL',
			'json' => 'JSON',
			'csv'  => 'CSV',
			'xml'  => 'XML',
			'yaml' => 'YAML',
		];

		return ( $overrides ) ?: $default;
	}

	public function getFormatEncoder( $format, $config = [] )
	{
		if ( is_array( $format ) ) {
			$format = $format['format'] ?? '';
			$config = $format;
		}

		switch ( $format ) {
			case 'url':
				// @todo Convert to actual class?
				return new class {
					public function encode( $data ) {
						return http_build_query( $data );
					}
					public function decode( $string ) {
						parse_str( $string, $parsed );
						return $parsed;
					}
				};

			case 'json':
				$defaultContext = [
					/*
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
				$defaultContext = [
					/*
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
				$defaultContext = [
					/*
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
				$defaultContext = [
					/*
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

	public function getFormatField( $formats = [], $defaults = [], $context = '' ): array
	{
		if ( ! $formats ) {
			$formats = $this->getFormats();
		}

		$fields = [];
		foreach ( $formats as $type => $name ) {
			switch ( $type ) {
				case 'json':
					$fields[] = $this->getFormatJsonFields( $defaults, $context );
				break;
				case 'csv':
					$fields[] = $this->getFormatCsvFields( $defaults, $context );
				break;
				case 'xml':
					$fields[] = $this->getFormatXmlFields( $defaults, $context );
				break;
				case 'yml':
				case 'yaml':
					$fields[] = $this->getFormatYamlFields( $defaults, $context );
				break;
			}
		}

		return [
			'label'   => 'Format',
			'type'    => 'select',
			'name'    => 'format',
			'default' => $defaults['format'] ?? null,
			'choices' => $formats,
			'fields'  => $fields,
		];
	}

	public function getFormatJsonFields( $defaults = [], $context = '' ): array
	{
		$fields = [
			'json_associative' => [
				'label' => 'Associative',
				'type' => 'checkbox',
				'default' => $defaults['json_associative'] ?? null,
				'conditionals' => [ 'format' => 'json' ],
				'context' => 'decode',
			],
		];

		if ( $context ) {
			$fields = $this->filterFieldsBy( [ 'context' => $context ], $fields );
		}
		return $fields;
	}

	public function getFormatCsvFields( $defaults = [], $context = '' ): array
	{
		$fields = [
			'csv_delimiter' => [
				'label' => 'Delimiter',
				'help' => 'Sets the field delimiter separating values (one character only)',
				'type' => 'text',
				'placeholder' => ',',
				'default' => $defaults['csv_delimiter'] ?? null,
				'conditionals' => [ 'format' => 'csv' ],
			],
			'csv_enclosure' => [
				'label' => 'Enclosure',
				'help' => 'Sets the field enclosure (one character only)',
				'type' => 'text',
				'placeholder' => '"',
				'default' => $defaults['csv_enclosure'] ?? null,
				'conditionals' => [ 'format' => 'csv' ],
			],
			'csv_escape_char' => [
				'label' => 'Escape character',
				'help' => 'Sets the escape character (at most one character)',
				'type' => 'text',
				'default' => $defaults['csv_escape_char'] ?? null,
				'conditionals' => [ 'format' => 'csv' ],
			],
			'csv_end_of_line'  => [
				'label' => 'End of line',
				'help' => 'Sets the character(s) used to mark the end of each line in the CSV file',
				'type' => 'text',
				'placeholder' => '\\n',
				'default' => $defaults['csv_end_of_line'] ?? null,
				'conditionals' => [ 'format' => 'csv' ],
			],
			'csv_headers' => [
				'label' => 'Headers columns',
				'help' => 'Sets the order of the header and data columns E.g.: if `$data = ["c" => 3, "a" => 1, "b" => 2]` and `$options = ["csv_headers" => ["a", "b", "c"]]` then `serialize($data, "csv", $options)` returns `a,b,c\n1,2,3`',
				'type' => 'text',
				'multiple' => true,
				'default' => $defaults['csv_headers'] ?? null,
				'conditionals' => [ 'format' => 'csv' ],
				'context' => 'encode',
			],
			'csv_key_separator' => [
				'label' => 'Key separator',
				'help' => 'Sets the separator for array\'s keys during its flattening',
				'type' => 'text',
				'placeholder' => '.',
				'default' => $defaults['csv_key_separator'] ?? null,
				'conditionals' => [ 'format' => 'csv' ],
			],
			'csv_escape_formula' => [
				'label' => 'Escape formula',
				'help' => 'Escapes fields containing formulas by prepending them with a `\t` character',
				'type' => 'checkbox',
				'default' => $defaults['csv_escape_formula'] ?? null,
				'conditionals' => [ 'format' => 'csv' ],
			],
			'csv_no_headers' => [
				'label' => 'No headers',
				'help' => 'Disables header in the encoded CSV',
				'type' => 'checkbox',
				'default' => $defaults['csv_no_headers'] ?? null,
				'conditionals' => [ 'format' => 'csv' ],
			],
			'csv_as_collection' => [
				'label' => 'As collection',
				'help' => 'Always returns results as a collection, even if only one line is decoded.',
				'type' => 'checkbox',
				'default' => $defaults['csv_as_collection'] ?? true,
				'conditionals' => [ 'format' => 'csv' ],
				'context' => 'decode',
			],
			'csv_output_utf8_bom' => [
				'label' => 'Output UTF8 Bom key',
				'type' => 'checkbox',
				'default' => $defaults['csv_output_utf8_bom'] ?? null,
				'conditionals' => [ 'format' => 'csv' ],
				'context' => 'decode',
			],
		];

		if ( $context ) {
			$fields = $this->filterFieldsBy( [ 'context' => $context ], $fields );
		}
		return $fields;
	}

	public function getFormatXmlFields( $defaults = [], $context = '' ): array
	{
		$fields = [
			'xml_version' => [
				'label' => 'Version',
				'help' => 'Sets the XML version attribute',
				'type' => 'text',
				'placeholder' => '1.0',
				'default' => $defaults['xml_version'] ?? null,
				'conditionals' => [ 'format' => 'xml' ],
			],
			'xml_encoding' => [
				'label' => 'Encoding',
				'help' => 'Sets the XML encoding attribute',
				'type' => 'text',
				'placeholder' => 'utf-8',
				'default' => $defaults['xml_encoding'] ?? null,
				'conditionals' => [ 'format' => 'xml' ],
			],
			'xml_root_node_name' => [
				'label' => 'Root node',
				'help' => 'Sets the root node name',
				'placeholder' => 'response',
				'type' => 'text',
				'default' => $defaults['xml_root_node_name'] ?? null,
				'conditionals' => [ 'format' => 'xml' ],
			],
			'xml_format_output' => [
				'label' => 'Format output',
				'help' => 'If set to true, formats the generated XML with line breaks and indentation',
				'type' => 'checkbox',
				'default' => $defaults['xml_format_output'] ?? null,
				'conditionals' => [ 'format' => 'xml' ],
				'context' => 'encode',
			],
			'xml_standalone' => [
				'label' => 'Standalone',
				'help' => 'Adds standalone attribute in the generated XML',
				'type' => 'checkbox',
				'default' => $defaults['xml_standalone'] ?? null,
				'conditionals' => [ 'format' => 'xml' ],
			],
			'xml_as_collection' => [
				'label' => 'As collection',
				'help' => 'Always returns results as a collection, even if only one line is decoded',
				'type' => 'checkbox',
				'default' => $defaults['xml_as_collection'] ?? null,
				'conditionals' => [ 'format' => 'xml' ],
				'context' => 'decode',
			],
			'xml_remove_empty_tags' => [
				'label' => 'Remove empty tags',
				'help' => 'Remove all empty tags in the generated XML',
				'type' => 'checkbox',
				'default' => $defaults['xml_remove_empty_tags'] ?? null,
				'conditionals' => [ 'format' => 'xml' ],
				'context' => 'encode',
			],
			'xml_type_cast_attributes' => [
				'label' => 'Type-cast attributes',
				'help' => 'This provides the ability to forget the attribute type casting',
				'type' => 'checkbox',
				'default' => $defaults['xml_type_cast_attributes'] ?? null,
				'conditionals' => [ 'format' => 'xml' ],
				'context' => 'decode',
			],
		];

		if ( $context ) {
			$fields = $this->filterFieldsBy( [ 'context' => $context ], $fields );
		}
		return $fields;
	}

	public function getFormatYamlFields( $defaults = [], $context = '' ): array
	{
		$fields = [
			'yaml_inline' => [
				'label' => 'Inline dump',
				'type' => 'checkbox',
				'default' => $defaults['yaml_inline'] ?? null,
				'conditionals' => [ 'format' => 'yaml' ],
				'context' => 'encode',
			],
			'yaml_indent' => [
				'label' => 'Indentation',
				'placeholder' => 'Number of spaces',
				'type' => 'number',
				'default' => $defaults['yaml_indent'] ?? null,
				'conditionals' => [ 'format' => 'yaml' ],
				'context' => 'encode',
			],
		];

		if ( $context ) {
			$fields = $this->filterFieldsBy( [ 'context' => $context ], $fields );
		}
		return $fields;
	}

	public function toFormat( string|array $format, array $data, array $config = [] ): array|string
	{
		$encoder = $this->getFormatEncoder( $format, $config );

		return ( $encoder ) ? $encoder->encode( $data, $format ) : $data;
	}

	public function fromFormat( string|array $format, string $data, array $config = [] ): array|string
	{
		$encoder = $this->getFormatEncoder( $format, $config );

		return ( $encoder ) ? $encoder->decode( $data, $format ) : $data;
	}
}
