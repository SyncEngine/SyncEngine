<?php

namespace App\Component;

class TagParser
{
	public $tagChar = '{{';
	public $tagSep = '.';

	public function hasTag( $value ): bool
	{
		if ( ! is_string( $value ) ) {
			return false;
		}
		return str_contains( $value, $this->tagChar );
	}

	public function parseTagArray( \ArrayAccess|array $resource, array $array ): array
	{
		$parsed = [];
		$count  = count( $array );
		$keys   = array_keys( $array );

		if ( ! $count || ! $keys ) {
			return $array;
		}

		$i = 0;
		do {
			$key = $this->parseTagString( $resource, $keys[ $i ] );
			$parsed[ $key ] = $array[ $keys[ $i ] ];

			if ( is_array( $parsed[ $key ] ) ) {
				$parsed[ $key ] = $this->parseTagArray( $resource, $parsed[ $key ] );
			} else {
				$parsed[ $key ] = $this->parseTagString( $resource, $parsed[ $key ] );
			}

			$i++;
		} while ( $i < $count );

		return $parsed;
	}

	public function parseTagString( \ArrayAccess|array $resource, string $value ): string
	{
		if ( ! $this->hasTag( $value ) ) {
			return $value;
		}

		$startChar = $this->tagChar;
		$endChar   = $startChar;
		if ( in_array( $endChar, [ '{{', '{', '[', '(' ] ) ) {
			$endChar = str_replace( [ '{', '[', '(' ], [ '}', ']', ')' ], $endChar );
		}

		$parts = explode( $startChar, $value );

		$count = count( $parts );
		$key   = 0;
		do {
			if ( ! str_contains( $parts[ $key ], $endChar ) ) {
				$key++;
				continue;
			}

			$part = explode( $endChar, $parts[ $key ] );

			$part[0] = $this->parseTag( $resource, $part[0] );

			$parts[ $key ] = implode( '', $part );

			$key++;
		} while ( $key < $count );

		return implode( '', $parts );
	}

	public function parseTag( \ArrayAccess|array $resource, string $tag = '' ): mixed
	{
		$value = '';
		$res   = $resource;
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
