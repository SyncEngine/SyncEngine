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

	public function encodeFormat( string|iterable $format, iterable $data, iterable $config = [] ): iterable|string
	{
		return ( new DataFormatter() )->encode( $format, $data, $config );
	}

	public function decodeFormat( string|iterable $format, string $data, iterable $config = [] ): iterable|string
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
			$codecs = $this->getContainer()->get( Codecs::class );
		} else {
			$codecs = DefaultController::get( Codecs::class );
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

	public function getFormatContentType( string|iterable $format, iterable $config = [] ): string
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
		/** @var CodecModel[] $codecs */
		$codecs = $this->getCodecs( $formats );

		if ( ! $formats ) {
			$formats = [
				'' => 'Plain',
			];
		}

		foreach ( $codecs as $codecModel ) {
			$formats[ $codecModel->getClassLocator() ] = $codecModel->getName();
		}

		return [
			'label'     => 'Format',
			'type'      => 'codec',
			'name'      => 'format',
			'default'   => $defaults['format'] ?? null,
			'direction' => $direction,
			'choices'   => $formats,
		];
	}
}
