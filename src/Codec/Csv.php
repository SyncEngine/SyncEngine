<?php

namespace SyncEngine\Codec;

use Symfony\Component\Serializer\Encoder\CsvEncoder as BaseCsvEncoder;
use SyncEngine\Model\CodecModel;
use SyncEngine\Service\Interface\CodecInterface;
use SyncEngine\Service\Serializer\CsvEncoder;

class Csv extends CodecModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'serializer';
		$this->name        = 'CSV';
		$this->description = $this->trans( 'Comma Separated Values' );
	}

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
	public function initEncoder( ?array $config = [] ): CodecInterface
	{
		$defaultContext = [];
		if ( $config ) {
			if ( isset( $config['delimiter'] ) ) {
				$defaultContext[ BaseCsvEncoder::DELIMITER_KEY ] = (string) $config['delimiter'];
			}
			if ( isset( $config['enclosure'] ) ) {
				$defaultContext[ BaseCsvEncoder::ENCLOSURE_KEY ] = (string) $config['enclosure'];
			}
			if ( isset( $config['escape_char'] ) ) {
				$defaultContext[ BaseCsvEncoder::ESCAPE_CHAR_KEY ] = (string) $config['escape_char'];
			}
			if ( isset( $config['end_of_line'] ) ) {
				$defaultContext[ BaseCsvEncoder::END_OF_LINE ] = (string) $config['end_of_line'];
			}
			if ( isset( $config['escape_formulas'] ) ) {
				$defaultContext[ BaseCsvEncoder::ESCAPE_FORMULAS_KEY ] = (bool) $config['escape_formulas'];
			}
			if ( isset( $config['headers'] ) ) {
				$defaultContext[ BaseCsvEncoder::HEADERS_KEY ] = (array) $config['headers'];
			}
			if ( isset( $config['key_separator'] ) ) {
				$defaultContext[ BaseCsvEncoder::KEY_SEPARATOR_KEY ] = (string) $config['key_separator'];
			}
			if ( isset( $config['no_headers'] ) ) {
				$defaultContext[ BaseCsvEncoder::NO_HEADERS_KEY ] = (bool) $config['no_headers'];
			}
			if ( isset( $config['as_collection'] ) ) {
				$defaultContext[ BaseCsvEncoder::AS_COLLECTION_KEY ] = (bool) $config['as_collection'];
			}
			if ( isset( $config['output_utf8_bom'] ) ) {
				$defaultContext[ BaseCsvEncoder::OUTPUT_UTF8_BOM_KEY ] = (bool) $config['output_utf8_bom'];
			}
		}

		return new CsvEncoder( defaultContext: $defaultContext );
	}

	public function getFormats(): array
	{
		return [ 'csv' ];
	}

	public function getContentType( array $config = [], string $format = '' ): string
	{
		return 'text/csv';
	}

	public function getCodecFields( $defaults = [] ): array
	{
		return [
			'delimiter'       => [
				'label'       => 'Delimiter',
				'help'        => 'Sets the field delimiter separating values (one character only)',
				'type'        => 'text',
				'placeholder' => ',',
				'default'     => $defaults['delimiter'] ?? null,
			],
			'enclosure'       => [
				'label'       => 'Enclosure',
				'help'        => 'Sets the field enclosure (one character only)',
				'type'        => 'text',
				'placeholder' => '"',
				'default'     => $defaults['enclosure'] ?? null,
			],
			'escape_char'     => [
				'label'      => 'Escape character',
				'help'       => 'Sets the escape character (at most one character)',
				'type'       => 'text',
				'default'    => $defaults['escape_char'] ?? null,
			],
			'end_of_line'     => [
				'label'       => 'End of line',
				'help'        => 'Sets the character(s) used to mark the end of each line in the CSV file',
				'type'        => 'text',
				'placeholder' => '\\n',
				'default'     => $defaults['end_of_line'] ?? null,
			],
			'headers'         => [
				'label'      => 'Headers columns',
				'help'       => 'Sets the order of the header and data columns E.g.: if `$data = ["c" => 3, "a" => 1, "b" => 2]` and `$options = ["headers" => ["a", "b", "c"]]` then `serialize($data, "csv", $options)` returns `a,b,c\n1,2,3`',
				'type'       => 'text',
				'multiple'   => true,
				'default'    => $defaults['headers'] ?? null,
				'_codec'     => 'encode',
			],
			'key_separator'   => [
				'label'       => 'Key separator',
				'help'        => 'Sets the separator for array\'s keys during its flattening',
				'type'        => 'text',
				'placeholder' => '.',
				'default'     => $defaults['key_separator'] ?? null,
			],
			'escape_formula'  => [
				'label'      => 'Escape formula',
				'help'       => 'Escapes fields containing formulas by prepending them with a `\t` character',
				'type'       => 'checkbox',
				'default'    => $defaults['escape_formula'] ?? null,
			],
			'no_headers'      => [
				'label'      => 'No headers',
				'help'       => 'Disables header in the encoded CSV',
				'type'       => 'checkbox',
				'default'    => $defaults['no_headers'] ?? null,
			],
			'as_collection'   => [
				'label'      => 'As collection',
				'help'       => 'Always returns results as a collection, even if only one line is decoded.',
				'type'       => 'checkbox',
				'default'    => $defaults['as_collection'] ?? true,
				'_codec'     => 'decode',
			],
			'output_utf8_bom' => [
				'label'      => 'Output UTF8 Bom key',
				'type'       => 'checkbox',
				'default'    => $defaults['output_utf8_bom'] ?? null,
				'_codec'     => 'decode',
			],
		];
	}
}
