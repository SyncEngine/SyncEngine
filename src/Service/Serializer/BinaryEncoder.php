<?php

namespace SyncEngine\Service\Serializer;

use SyncEngine\Service\Interface\CodecInterface;

class BinaryEncoder implements CodecInterface
{
	const FORMAT = 'binary';
	private const ALTERNATIVE_FORMAT = 'base64';

	public function encode( mixed $data, string $format, array $context = [] ): string
	{
		return base64_encode( $data );
	}

	public function decode( string $data, string $format, array $context = [] ): false|string
	{
		if ( ! isset( $context['strict'] ) ) {
			// Only allow stdClass by default for security unless overwritten.
			$context['strict'] = false;
		}

		return base64_decode( $data, $context['strict'] );
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
