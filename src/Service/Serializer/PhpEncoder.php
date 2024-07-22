<?php

namespace SyncEngine\Service\Serializer;

use SyncEngine\Service\Interface\CodecInterface;

class PhpEncoder implements CodecInterface
{
	const FORMAT = 'php';

	public function encode( mixed $data, string $format, array $context = [] ): string
	{
		return serialize( $data );
	}

	public function decode( string $data, string $format, array $context = [] ): array
	{
		if ( ! isset( $context['allowed_classes'] ) ) {
			// Only allow stdClass by default for security unless overwritten.
			$context['allowed_classes'] = [ 'stdClass' ];
		}

		return @unserialize( $data, $context );
	}

	public function supportsEncoding( string $format ): bool
	{
		return self::FORMAT === $format;
	}

	public function supportsDecoding( string $format ): bool
	{
		return self::FORMAT === $format;
	}
}
