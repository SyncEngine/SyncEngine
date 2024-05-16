<?php

namespace SyncEngine\Service\Serializer;

use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use SyncEngine\Service\Interface\CodecInterface;

class FormDataEncoder implements CodecInterface
{
	const FORMAT = 'formdata';

	public function encode( mixed $data, string $format, array $context = [] ): string
	{
		return ( new FormDataPart( $data ) )->bodyToString();
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
		return false;
	}
}
