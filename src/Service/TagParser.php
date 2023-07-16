<?php

namespace App\Service;

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
			$field = $parts[ $key ];
			if ( is_object( $res ) && ! $res instanceof \ArrayAccess ) {
				// @todo Normalize object.
				if ( isset( $res->$field ) ) {
					$res = $res->$field;
				} elseif ( is_callable( [ $res, 'get' . ucfirst( $field ) ] ) ) {
					$res = call_user_func( [ $res, 'get' . ucfirst( $field ) ] );
				} else {
					$res = null;
					break;
				}
			} else {
				$res = $res[ $field ] ?? null;
			}

			if ( null === $res ) {
				break;
			}

			$key++;
			if ( $key === $count ) {
				$value = $res;
				break;
			}
		} while ( $key < $count );

		return $value;
	}
}
