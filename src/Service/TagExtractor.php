<?php

namespace App\Service;

use App\Model\DatasetModel;

class TagExtractor
{
	protected ResourceData $resource;
	public string $tagStartChar = '{{';
	public string $tagEndChar = '}}';
	public string $tagFilterChar = '|';

	public function __construct(
		array|object $resource = [],
	) {
		if ( ! $resource instanceof ResourceData ) {
			$resource = new ResourceData( $resource );
		}
		$this->resource = $resource;
	}

	public function hasTag( $value, string $tag = '' ): bool
	{
		if ( ! is_string( $value ) ) {

			if ( is_array( $value ) ) {
				foreach ( $value as $val ) {
					if ( $this->hasTag( $val ) ) {
						return true;
					}
				}
			}

			return false;
		}
		if ( ! str_contains( $value, $this->tagStartChar ) ) {
			return false;
		}

		// Compare to specific tag.
		if ( $tag ) {
			$parts = $this->getTagParts( $value );
			foreach ( $this->getTagParts( $tag ) as $index => $part ) {
				if ( ! isset( $parts[ $index ] ) ) {
					return false;
				}
				if ( $parts[ $index ] !== $part ) {
					return false;
				}
			}
		}

		return true;
	}

	public function getTagParts( string $tag ): array
	{
		$hasFilter = strpos( $tag, $this->tagFilterChar );
		if ( $hasFilter ) {
			$tag = substr( $tag, 0, $hasFilter );
		}

		return (array) $this->resource->parseKey( trim( $tag, ' ' . $this->tagStartChar . $this->tagEndChar ) );
	}

	public function extractTags( $value ): array
	{
		$tags = [];

		if ( ! is_string( $value ) ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $val ) {
					$tags = array_merge( $tags, $this->extractTags( $val ) );
				}
			}
			return $tags;
		}

		if ( ! $this->hasTag( $value ) ) {
			return $tags;
		}

		$parts = explode( $this->tagStartChar, $value );

		if ( empty( $parts[0] ) && ! empty ( $parts[1] ) && ! isset( $parts[2] ) && str_ends_with( $parts[1], $this->tagEndChar ) ) {
			// Just a single tag. Can return non-string value.
			return [
				trim( $parts[1], ' ' . $this->tagEndChar ),
			];
		}

		$count = count( $parts );
		$key   = 0;
		do {
			if ( ! str_contains( $parts[ $key ], $this->tagEndChar ) ) {
				$key++;
				continue;
			}

			$part = explode( $this->tagEndChar, $parts[ $key ] );

			$tags[] = trim( $part[0] );

			$key++;
		} while ( $key < $count );

		return $tags;
	}
}
