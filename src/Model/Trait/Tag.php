<?php

namespace App\Model\Trait;

trait Tag
{
	public $tagChar = '{{';
	public $tagSep = '.';

	public function hasTag( $value ): bool
	{
		if ( ! is_string( $value ) ) {
			return false;
		}
		return ( -1 !== strpos( $value, $this->tagChar ) );
	}

	public function parseString( \ArrayAccess $resource, string $value = null ): string
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
			if ( false === strpos( $parts[ $key ], $endChar ) ) {
				continue;
			}

			$part = explode( $endChar, $parts[ $key ] );

			$part[0] = $this->parseTag( $resource, $part[0] );

			$parts[ $key ] = implode( '', $part );

			$key++;
		} while ( $key < $count );

		return implode( '', $parts );
	}

	public function parseTag( \ArrayAccess $resource, string $tag = null ): mixed
	{
		$value = '';
		$parts = explode( $this->tagSep, trim( $tag ) );
		$res   = $resource;

		$count = count( $parts );
		$key   = 0;
		do {
			$res = $res[ $parts[ $key ] ] ?? null;

			if ( null === $res ) {
				break;
			}

			if ( $key === $count ) {
				$value = $res;
			}

			$key++;
		} while ( $key < $count );

		return $value;
	}
}
