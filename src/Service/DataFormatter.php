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
			return $this->getEncoder( $format, $config )?->encode( $data ) ?? $data;

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
			return $this->getEncoder( $format, $config )?->decode( $data ) ?? $data;

		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			if ( 'syntax error' === strtolower( $message ) ) {
				$message = $e::class . ': ' . $message;
			}
			throw new CodecException( $message, $e->getCode(), $e );
		}
	}

	public function getContentType( $format ): string
	{
		return $this->getEncoder( $format )?->getContentType( $format ) ?? '';
	}

	public function getEncoder( $format, $config = [] ): ?CodecModel
	{
		if ( $format instanceof CodecModel ) {
			return $format;
		}

		$formatType   = $format;
		$formatConfig = [];
		if ( is_iterable( $format ) ) {
			$formatType   = $format['_class'] ?? $format['format'] ?? '';
			$formatConfig = $format;
		}

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
