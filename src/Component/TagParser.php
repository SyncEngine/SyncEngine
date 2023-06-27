<?php

namespace App\Component;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class TagParser
{
	public string $tagStartChar = '{{';
	public string $tagEndChar = '}}';
	public string $tagSep = '.';
	public array $resource = [];

	public function __construct( array|object $resource )
	{
		$this->resource = $resource;
	}

	public function hasTag( $value ): bool
	{
		if ( ! is_string( $value ) ) {
			return false;
		}
		return str_contains( $value, $this->tagStartChar );
	}

	public function parseTagArray( array $array ): array
	{
		$parsed = [];
		$count  = count( $array );
		$keys   = array_keys( $array );

		if ( ! $count || ! $keys ) {
			return $array;
		}

		$i = 0;
		do {
			$key = $this->parseTagString( $keys[ $i ] );
			$parsed[ $key ] = $array[ $keys[ $i ] ];

			if ( is_array( $parsed[ $key ] ) ) {
				$parsed[ $key ] = $this->parseTagArray( $parsed[ $key ] );
			} else {
				$parsed[ $key ] = $this->parseTagString( $parsed[ $key ] );
			}

			$i++;
		} while ( $i < $count );

		return $parsed;
	}

	public function parseTagString( string $value ): string
	{
		if ( ! $this->hasTag( $value ) ) {
			return $value;
		}

		$parts = explode( $this->tagStartChar, $value );

		$count = count( $parts );
		$key   = 0;
		do {
			if ( ! str_contains( $parts[ $key ], $this->tagEndChar ) ) {
				$key++;
				continue;
			}

			$part = explode( $this->tagEndChar, $parts[ $key ] );

			$part[0] = $this->parseTag( $part[0] );

			$parts[ $key ] = implode( '', $part );

			$key++;
		} while ( $key < $count );

		return implode( '', $parts );
	}

	public function parseTag( string $tag = '' ): mixed
	{
		$value = '';
		$res   = $this->resource;
		$parts = explode( $this->tagSep, trim( $tag ) );

		$count = count( $parts );
		$key   = 0;
		do {
			$res = $res[ $parts[ $key ] ] ?? null;

			if ( null === $res ) {
				break;
			}

			$key++;
			if ( $key === $count ) {
				$value = $res;
				break;
			}

			if ( is_object( $res ) ) {
				$res = get_object_vars( $res );
			}
		} while ( $key < $count );

		return $value;
	}
}
