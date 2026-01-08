<?php

namespace SyncEngine\Service;

use SyncEngine\Exception\CodecException;
use SyncEngine\Model\CodecModel;
use SyncEngine\Model\Trait\Format;

class DataFormatter
{
	use Format;

	public function encode( string|array|CodecModel $format, array $data, array $config = [] ): array|string
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

	public function decode( string|array|CodecModel $format, string $data, array $config = [] ): array|string
	{
		try {
			$encoder = $this->getEncoder( $format, $config );
			$format  = $this->getFormat( $format, $config );

			return ( $encoder ) ? $encoder->decode( $data, $format ) : $data;

		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			if ( 'syntax error' === strtolower( $message ) ) {
				$message = $e::class . ': ' . $message;
			}
			throw new CodecException( $message, $e->getCode(), $e );
		}
	}

	public function getFormat( string|array|CodecModel $format, array $config = [] ): string
	{
		if ( is_iterable( $format ) ) {
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

		$formatType   = $this->getFormat( $format );
		$formatConfig = is_iterable( $format ) ? $format : [];

		$prefix = $formatType . '_';
		foreach ( $config as $key => $value ) {
			if ( str_starts_with( $key, $prefix ) ) {
				if ( ! isset( $formatConfig[ $key ] ) ) {
					$formatConfig[ $key ] = $value;
				}
				$context = substr( $key, strlen( $prefix ) );
				if ( ! isset( $formatConfig[ $context ] ) ) {
					$formatConfig[ $context ] = $value;
				}
			}
		}

		$codec = CodecModel::get( $formatType );

		$codec?->setEncoder( $formatConfig );

		return $codec;
	}
}
