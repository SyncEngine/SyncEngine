<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Model\CodecModel;
use SyncEngine\Service\DataFormatter;
use SyncEngine\Service\Provider\Codecs;

trait Format
{
	use Fields;

	public function encodeFormat( string|array $format, array $data, array $config = [] ): array|string
	{
		return ( new DataFormatter() )->encode( $format, $data, $config );
	}

	public function decodeFormat( string|array $format, string $data, array $config = [] ): array|string
	{
		return ( new DataFormatter() )->decode( $format, $data, $config );
	}

	/**
	 * @return CodecModel[]
	 */
	public function getCodecs( $formats = [] ): array
	{
		/** @var Codecs $codecs */
		if ( $this instanceof AbstractModel ) {
			$codecs = $this->getContainer()->get( 'Codecs' );
		} else {
			$codecs = DefaultController::get( 'Codecs' );
		}

		if ( $formats ) {
			$list = [];
			foreach ( $formats as $format ) {
				$codec = $codecs->get( $format );
				if ( $codec ) {
					$list[ $format ] = $codec;
				}
			}
			return $list;
		}

		return $codecs->getAll();
	}

	public function getFormats(): array
	{
		$default = [
			'' => 'Plain',
		];

		foreach ( $this->getCodecs() as $codec ) {
			foreach ( $codec->getFormats() as $format ) {
				$default[ $format ] = $codec;
			}
		}

		return $default;
	}

	public function getFormatContentType( string|array $format, array $config = [] ): string
	{
		return ( new DataFormatter() )->getContentType( $format, $config );
	}

	public function getFormatDecodeField( $formats = [], $defaults = [] ): array
	{
		return $this->getFormatField( $formats, $defaults, 'decode' );
	}

	public function getFormatEncodeField( $formats = [], $defaults = [] ): array
	{
		return $this->getFormatField( $formats, $defaults, 'encode' );
	}

	public function getFormatField( $formats = [], $defaults = [], $direction = '' ): array
	{
		$codecs = $this->getCodecs( $formats );

		if ( ! $formats ) {
			$formats = [
				'' => 'Plain',
			];
		}

		$fields = [];
		foreach ( $codecs as $codecModel ) {
			$codecFields = match ( $direction ) {
				'encode' => $codecModel->getEncodeFields( $defaults ),
				'decode' => $codecModel->getDecodeFields( $defaults ),
				default => $codecModel->getCodecFields( $defaults ),
			};

			$format = strtolower( $codecModel->getClassLocator() );
			$formats[ $format ] = $codecModel->getName();

			foreach ( $codecFields as $key => $field ) {
				$fields[ $format . '_' . $key ] = array_merge_recursive( $field, [
					'conditions' => [ 'format' => $format ]
				] );
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
}
