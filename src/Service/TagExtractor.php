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

	public function isTag( string $value, string $tag ): bool
	{
		$value = $this->getTagParts( $value );
		$tag   = $this->getTagParts( $tag );

		foreach ( $tag as $index => $part ) {
			if ( ! isset( $value[ $index ] ) ) {
				return false;
			}
			if ( $value[ $index ] !== $part ) {
				return false;
			}
		}
		return true;
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

		if ( ! str_contains( $value, $this->tagStartChar ) || ! str_contains( $value, $this->tagEndChar ) ) {
			// No tag found at all.
			return false;
		}

		// Compare to specific tag.
		if ( $tag ) {
			return ! empty( $this->_extract( $value, $tag ) );
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

	public function extractTags( $value, string $tag = '' ): array
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

		return $this->_extract( $value, $tag );
	}

	protected function _extract( string $value, string $tag = '' ): array
	{
		$tags  = [];
		$parts = explode( $this->tagStartChar, $value );

		if ( empty( $parts[0] ) && ! empty ( $parts[1] ) && ! isset( $parts[2] ) && str_ends_with( $parts[1], $this->tagEndChar ) ) {
			// Just a single tag.

			if ( $tag && ! $this->isTag( $parts[1], $tag ) ) {
				return [];
			}

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

			if ( ! $tag || $this->isTag( $part[0], $tag ) ) {
				$tags[] = trim( $part[0] );
			}

			$key++;
		} while ( $key < $count );

		return $tags;
	}
}
