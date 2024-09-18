<?php

namespace SyncEngine\Codec;

use Symfony\Component\Serializer\Encoder\XmlEncoder as BaseXmlEncoder;
use SyncEngine\Model\CodecModel;
use SyncEngine\Service\Interface\CodecInterface;
use SyncEngine\Service\Serializer\XmlEncoder;

class Xml extends CodecModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'serializer';
		$this->name        = 'XML';
		$this->description = $this->trans( 'Extensible Markup Language' );
	}

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
	public function initEncoder( ?array $config = [] ): CodecInterface
	{
		$defaultContext = [];
		if ( $config ) {
			if ( isset( $config['format_output'] ) ) {
				$defaultContext[ BaseXmlEncoder::FORMAT_OUTPUT ] = (bool) $config['format_output'];
			}
			if ( isset( $config['version'] ) ) {
				$defaultContext[ BaseXmlEncoder::VERSION ] = (bool) $config['version'];
			}
			if ( isset( $config['encoding'] ) ) {
				$defaultContext[ BaseXmlEncoder::ENCODING ] = (bool) $config['encoding'];
			}
			if ( isset( $config['standalone'] ) ) {
				$defaultContext[ BaseXmlEncoder::STANDALONE ] = (bool) $config['standalone'];
			}
			if ( isset( $config['as_collection'] ) ) {
				$defaultContext[ BaseXmlEncoder::AS_COLLECTION ] = (bool) $config['as_collection'];
			}
			if ( isset( $config['remove_empty_tags'] ) ) {
				$defaultContext[ BaseXmlEncoder::REMOVE_EMPTY_TAGS ] = (bool) $config['remove_empty_tags'];
			}
			if ( isset( $config['root_node_name'] ) ) {
				$defaultContext[ BaseXmlEncoder::ROOT_NODE_NAME ] = (string) $config['root_node_name'];
			}
			if ( isset( $config['type_cast_attributes'] ) ) {
				$defaultContext[ BaseXmlEncoder::TYPE_CAST_ATTRIBUTES ] = (bool) $config['type_cast_attributes'];
			}
		}

		return new XmlEncoder( defaultContext: $defaultContext );
	}

	public function getFormats(): array
	{
		return [ 'xml' ];
	}

	public function getContentType( array $config = [], string $format = '' ): string
	{
		return 'application/xml';
	}

	public function getCodecFields( $defaults = [] ): array
	{
		return [
			'version'              => [
				'label'       => 'Version',
				'help'        => 'Sets the XML version attribute',
				'type'        => 'text',
				'placeholder' => '1.0',
				'default'     => $defaults['version'] ?? null,
			],
			'encoding'             => [
				'label'       => 'Encoding',
				'help'        => 'Sets the XML encoding attribute',
				'type'        => 'text',
				'placeholder' => 'utf-8',
				'default'     => $defaults['encoding'] ?? null,
			],
			'root_node_name'       => [
				'label'       => 'Root node',
				'help'        => 'Sets the root node name',
				'placeholder' => 'response',
				'type'        => 'text',
				'default'     => $defaults['root_node_name'] ?? null,
			],
			'format_output'        => [
				'label'      => 'Format output',
				'help'       => 'If set to true, formats the generated XML with line breaks and indentation',
				'type'       => 'checkbox',
				'default'    => $defaults['format_output'] ?? null,
				'_codec'     => 'encode',
			],
			'standalone'           => [
				'label'      => 'Standalone',
				'help'       => 'Adds standalone attribute in the generated XML',
				'type'       => 'checkbox',
				'default'    => $defaults['standalone'] ?? null,
			],
			'as_collection'        => [
				'label'      => 'As collection',
				'help'       => 'Always returns results as a collection, even if only one line is decoded',
				'type'       => 'checkbox',
				'default'    => $defaults['as_collection'] ?? null,
				'_codec'     => 'decode',
			],
			'remove_empty_tags'    => [
				'label'      => 'Remove empty tags',
				'help'       => 'Remove all empty tags in the generated XML',
				'type'       => 'checkbox',
				'default'    => $defaults['remove_empty_tags'] ?? null,
				'_codec'     => 'encode',
			],
			'type_cast_attributes' => [
				'label'      => 'Type-cast attributes',
				'help'       => 'This provides the ability to forget the attribute type casting',
				'type'       => 'checkbox',
				'default'    => $defaults['type_cast_attributes'] ?? null,
				'_codec'     => 'decode',
			],
		];
	}
}
