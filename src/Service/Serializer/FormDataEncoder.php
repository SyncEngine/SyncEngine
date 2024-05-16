<?php

namespace SyncEngine\Service\Serializer;

use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use SyncEngine\Service\Interface\CodecInterface;

class FormDataEncoder implements CodecInterface
{
	const FORMAT = 'formdata';

	protected function recursiveToString( $data ): array|string
	{
		if ( is_array( $data ) ) {
			return array_map( [ $this, 'recursiveToString' ], $data );
		}

		return (string) $data;
	}

	public function encode( mixed $data, string $format, array $context = [] ): string
	{
		return ( new FormDataPart( (array) $this->recursiveToString( $data ) ) )->bodyToString();
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
