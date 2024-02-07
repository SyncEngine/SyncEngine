<?php

namespace SyncEngine\Service\Serializer;

use SyncEngine\Service\Interface\CodecInterface;

class UrlEncoder implements CodecInterface
{
	const FORMAT = 'url';

	public function encode( mixed $data, string $format, array $context = [] ): string
	{
		return http_build_query( $data );
	}

	public function decode( string $data, string $format, array $context = [] ): array
	{
		parse_str( $data, $parsed );

		return $parsed;
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
