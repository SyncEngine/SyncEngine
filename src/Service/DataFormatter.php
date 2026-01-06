<?php

namespace SyncEngine\Service;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Exception\CodecException;
use SyncEngine\Model\CodecModel;
use SyncEngine\Model\Trait\Format;
use SyncEngine\Service\Provider\Codecs;

class DataFormatter
{
	use Format;

	public function encode( string|array $format, array $data, array $config = [] ): array|string
	{
		try {
			$format  = $this->getFormat( $format, $config );
			$encoder = $this->getEncoder( $format, $config );

			return ( $encoder ) ? $encoder->encode( $data, $format ) : $data;

		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			if ( 'syntax error' === strtolower( $message ) ) {
				$message = $e::class . ': ' . $message;
			}
			throw new CodecException( $message, $e->getCode(), $e );
		}
	}

	public function decode( string|array $format, string $data, array $config = [] ): array|string
	{
		try {
			$format  = $this->getFormat( $format, $config );
			$encoder = $this->getEncoder( $format, $config );

			return ( $encoder ) ? $encoder->decode( $data, $format ) : $data;

		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			if ( 'syntax error' === strtolower( $message ) ) {
				$message = $e::class . ': ' . $message;
			}
			throw new CodecException( $message, $e->getCode(), $e );
		}
	}

	public function getFormat( string|array $format, array $config = [] ): string
	{
		if ( is_array( $format ) ) {
			return $format['_class'] ?? $format['format'] ?? '';
		}

		/*switch ( $format ) {
			case 'xls':
				$format = $config['xls_type'] ?? $format;
			break;
		}*/

		return $format;
	}

	public function getContentType( $format )
	{
		$format = $this->getFormat( $format );

		$encoder = $this->getEncoder( $format );

		if ( $encoder && method_exists( $encoder, 'getContentType' ) ) {
			return $encoder->getContentType();
		}

		return '';
	}

	public function getEncoder( $format, $config = [] ): ?CodecModel
	{
		if ( $format instanceof CodecModel ) {
			return $format;
		}

		/** @var Codecs $codecs */
		$codecs = DefaultController::get( 'Codecs' );

		$format = $this->getFormat( $format );
		/** @var CodecModel $codec */
		$codec = $codecs->get( $format );

		$prefix = $format . '_';
		foreach ( $config as $key => $value ) {
			if ( str_starts_with( $key, $prefix ) ) {
				$context = substr( $key, strlen( $prefix ) );
				if ( ! isset( $config[ $context ] ) ) {
					$config[ $context ] = $value;
				}
			}
		}

		$codec->setEncoder( $config );

		return $codec;
	}
}
