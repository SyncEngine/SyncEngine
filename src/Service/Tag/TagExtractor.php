<?php

namespace SyncEngine\Service\Tag;

use SyncEngine\Structure\Data\ResourceData;

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

	public function isSingleTag( string $value ): bool
	{
		return (
			str_starts_with( $value, $this->tagStartChar ) &&
			str_ends_with( $value, $this->tagEndChar ) &&
			1 === substr_count( $value, $this->tagStartChar ) &&
			1 === substr_count( $value, $this->tagEndChar )
		);
	}

	public function hasTag( $value, string $tag = '' ): bool
	{
		if ( ! is_string( $value ) ) {

			if ( is_array( $value ) ) {
				foreach ( $value as $val ) {
					if ( $this->hasTag( $val, $tag ) ) {
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
			return ! empty( $this->_extractTags( $value, $tag ) );
		}

		$pos = strpos( $value, $this->tagStartChar );

		return $pos < strpos( $value, $this->tagEndChar );
	}

	public function getTagParts( string $tag ): array
	{
		$hasFilter = strpos( $tag, $this->tagFilterChar );
		if ( $hasFilter ) {
			$tag = substr( $tag, 0, $hasFilter );
		}

		return (array) $this->resource->parseKey( trim( $tag, ' ' . $this->tagStartChar . $this->tagEndChar ) );
	}

	public function getTagPart( string $tag, $index = 0 ): ?string
	{
		return $this->getTagParts( $tag )[ $index ] ?? null;
	}

	public function extractTags( $value, string $tag = '' ): array
	{
		$tags = [];

		if ( ! is_string( $value ) ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $val ) {
					$tags = array_merge( $tags, $this->extractTags( $val, $tag ) );
				}
			}

			return $tags;
		}

		if ( ! $this->hasTag( $value ) ) {
			return $tags;
		}

		return $this->_extractTags( $value, $tag );
	}

	protected function _extractTags( string $value, string $tag = '' ): array
	{
		$tags = [];

		if ( $this->isSingleTag( $value ) ) {
			// Just a single tag.
			$value = trim( $value, $this->tagStartChar . ' ' . $this->tagEndChar );

			if ( $tag && ! $this->isTag( $value, $tag ) ) {
				return [];
			}

			return [ $value ];
		}

		$parts = explode( $this->tagStartChar, $value );

		$count = count( $parts );
		$key   = 0;
		do {
			if ( ! str_contains( $parts[ $key ], $this->tagEndChar ) ) {
				$key ++;
				continue;
			}

			$part = explode( $this->tagEndChar, $parts[ $key ] );

			if ( ! $tag || $this->isTag( $part[0], $tag ) ) {
				$tags[] = trim( $part[0] );
			}

			$key ++;
		} while ( $key < $count );

		return $tags;
	}
}
