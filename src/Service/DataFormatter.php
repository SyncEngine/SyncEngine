<?php

namespace SyncEngine\Service;

use SyncEngine\Exception\CodecException;
use SyncEngine\Model\CodecModel;
use SyncEngine\Model\Trait\Format;

class DataFormatter
{
	use Format;

	public function encode( string|iterable|CodecModel $format, iterable $data, iterable $config = [] ): iterable|string
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

	public function decode( string|iterable|CodecModel $format, string $data, iterable $config = [] ): iterable|string
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

	public function getContentType( $format, iterable $config = [] ): string
	{
		return $this->getEncoder( $format, $config )?->getContentType( $config, $format ) ?? '';
	}

	public function getEncoder( $format, iterable $config = [] ): ?CodecModel
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
