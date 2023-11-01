<?php

namespace App\Service;

use App\Model\DatasetModel;

class TagParser
{
	public string $tagStartChar = '{{';
	public string $tagEndChar = '}}';
	public string $tagSep = '.';
	public string $tagFilter = '|';

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

	public function getTagParts( string $tag ): array
	{
		$hasFilter = strpos( $tag, $this->tagFilter );
		if ( $hasFilter ) {
			$tag = substr( $tag, 0, $hasFilter );
		}

		return explode( $this->tagSep, trim( $tag, ' {}' ) );
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
		$tag = array_map( 'trim', explode( $this->tagFilter, $tag ) );

		$value = '';
		$res   = $this->resource;
		$parts = $this->getTagParts( $tag[0] );

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
				// @todo Normalize object?
				if ( isset( $res->$field ) ) {
					$res = $res->$field;
				} elseif ( is_callable( [ $res, 'get' . ucfirst( $field ) ] ) ) {
					$res = call_user_func_array( [ $res, 'get' . ucfirst( $field ) ], array_slice( $parts, $key + 1 ) );
				} else {
					$res = null;
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

		if ( ! empty( $tag[1] ) ) {
			$value = $this->filterTag( $value, $tag[1] );
		}

		return $value;
	}

	public function filterTag( $value, string $filter ): mixed
	{
		$filter = array_map( 'trim', explode( '(', $filter ) );

		$callable = [ $this, 'filter' . ucfirst( $filter[0] ) ];
		if ( ! is_callable( $callable ) ) {
			$callable = is_callable( $filter[0] ) ? $filter[0] : null;
		}

		if ( $callable ) {
			$args = [
				$value,
			];

			if ( ! empty( $filter[1] ) ) {
				$filter[1] = rtrim( $filter[1], ')' );
				$filter[1] = json_decode( '[' . $filter[1] . ']' );

				$args = array_push( $args, ...$filter[1] );
			}

			$value = call_user_func_array( $callable, $args );
		}

		return $value;
	}

	public function filterFormat( $value, string $format = ',', ...$config ): string
	{
		if ( is_string( $value ) ) {
			return $value;
		}

		$formatter = new Formatter();

		return (string) $formatter->toFormat( $format, $value, $config );
	}

	public function filterString( $value, string $separator = ',', ...$config ): string
	{
		if ( is_string( $value ) ) {
			return $value;
		}

		if ( 2 < strlen( $separator ) ) {
			$formatter = new Formatter();

			if ( array_key_exists( $separator, $formatter->getFormats() ) ) {
				return $formatter->toFormat( $separator, $value, $config );
			}
		}

		return implode( $separator, $value );
	}
}
