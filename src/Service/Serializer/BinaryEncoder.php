<?php

namespace SyncEngine\Service\Serializer;

use SyncEngine\Service\Interface\CodecInterface;

class BinaryEncoder implements CodecInterface
{
	const FORMAT = 'binary';
	private const ALTERNATIVE_FORMAT = 'base64';

	public function encode( mixed $data, string $format, array $context = [] ): string
	{
		if ( 'base64' === $format ) {
			return base64_encode( $data );
		}
		return base_convert( unpack( 'H*', $data ), 16, 2 );
	}

	public function decode( string $data, string $format, array $context = [] ): false|string
	{
		if ( 'base64' === $format ) {
			$decoded = base64_decode( $data );
		} else {
			$decoded = pack( 'H*', base_convert( $data, 2, 16 ) );
		}

		if ( false === $decoded ) {
			$decoded = '';
		}

		return $decoded;
	}

	public function supportsEncoding( string $format ): bool
	{
		return self::FORMAT === $format || self::ALTERNATIVE_FORMAT === $format;
	}

	public function supportsDecoding( string $format ): bool
	{
		return self::FORMAT === $format || self::ALTERNATIVE_FORMAT === $format;
	}
}
