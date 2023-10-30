<?php

namespace App\Service;

use App\Model\DatasetModel;

class TagParser
{
	public string $tagStartChar = '{{';
	public string $tagEndChar = '}}';
	public string $tagSep = '.';

	public function __construct(
		public array|object $resource = [],
		public bool $cleanMode = true,
	) {}

	public function setCleanMode( bool $mode ) {
		$this->cleanMode = $mode;
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
			} elseif ( is_scalar( $parsed[ $key ] ) ) {
				$parsed[ $key ] = $this->parseTagString( (string) $parsed[ $key ] );
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

			$parsed = $this->parseTag( $part[0] );

			if ( $this->cleanMode || $parsed ) {
				$part[0] = $parsed;
				$parts[ $key ] = implode( '', $part );
			} else {
				$parts[ $key ] = $this->tagStartChar . implode( $this->tagEndChar, $part );
			}

			$key++;
		} while ( $key < $count );

		return implode( '', $parts );
	}

	public function parseTag( string $tag = '' ): mixed
	{
		$value = '';
		$res   = $this->resource;
		$parts = explode( $this->tagSep, trim( $tag ) );

		$first = reset( $parts );

		// Allow fetching a dataset.
		// @todo restrict access to datasets?
		if ( 'dataset' === $first ) {
			array_shift( $parts );
			$id_or_ref = array_shift( $parts );

			$dataset = DatasetModel::get( $id_or_ref );
			if ( ! $dataset ) {
				return $value;
			}

			$res = $dataset;
		}

		$count = count( $parts );
		$key   = 0;
		do {
			$field = $parts[ $key ];
			if ( is_object( $res ) && ! $res instanceof \ArrayAccess ) {
				// @todo Normalize object.
				if ( isset( $res->$field ) ) {
					$res = $res->$field;
				} elseif ( is_callable( [ $res, 'get' . ucfirst( $field ) ] ) ) {
					$res = call_user_func_array( [ $res, 'get' . ucfirst( $field ) ], array_slice( $parts, $key + 1 ) );
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
