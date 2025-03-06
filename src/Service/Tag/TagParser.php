<?php

namespace SyncEngine\Service\Tag;

use SyncEngine\Model\StorageModel;
use SyncEngine\Service\Data\ResourceData;

class TagParser
{
	use TagFilter;

	protected ResourceData $resource;
	protected TagExtractor $extractor;
	protected bool $recurseMode;
	protected bool $strictMode;
	protected bool $cleanMode;
	protected array $cleanWhitelist;
	public string $tagStartChar = '{{';
	public string $tagEndChar = '}}';
	public string $tagEnclosureChar = '"';
	public string $tagFilterChar = '|';
	public string $tagSubStartChar = '<{';
	public string $tagSubEndChar = '}>';

	public function __construct(
		array|object $resource = [],
		array|bool $clean = true,
		bool $strict = false,
		bool $recurse = true,
	) {
		if ( ! $resource instanceof ResourceData ) {
			$resource = new ResourceData( $resource );
		}
		$this->resource   = $resource;
		$this->strictMode = $strict;
		$this->recurseMode = $recurse;

		$this->setCleanMode( $clean );

		$this->extractor = new TagExtractor( $resource );
	}

	public function setCleanMode( bool|array $mode ): self
	{
		if ( is_array( $mode ) ) {
			$this->setCleanWhitelist( $mode );
			$mode = true;
		}

		$this->cleanMode = $mode;

		return $this;
	}

	public function setCleanWhitelist( array $whitelist ): self
	{
		$this->cleanWhitelist = $whitelist;

		$this->setCleanMode( true );

		return $this;
	}

	public function isWhitelisted( string $tag ): bool
	{
		if ( empty( $this->cleanWhitelist ) ) {
			return false;
		}

		$tagParts    = $this->extractor->getTagParts( $tag );
		$whitelist   = $this->cleanWhitelist;
		$whitelisted = false;

		foreach ( $tagParts as $tagPart ) {
			if ( ! is_array( $whitelist ) ) {
				if ( $tagPart === $whitelist ) {
					$whitelisted = true;
				}
				break;
			}
			if ( empty( $whitelist ) ) {
				// Empty whitelist key means all subs are allowed.
				$whitelisted = true;
				break;
			}
			if ( in_array( $tagPart, $whitelist, true ) ) {
				$whitelisted = true;
				break;
			}
			if ( ! isset( $whitelist[ $tagPart ] ) ) {
				break;
			}
			if ( ! is_iterable( $whitelist[ $tagPart ] ) ) {
				$whitelisted = true;
				break;
			}
			$whitelist = $whitelist[ $tagPart ];
		}

		return $whitelisted;
	}

	public function hasTag( $value, string $tag = '' ): bool
	{
		return $this->extractor->hasTag( $value, $tag );
	}

	public function getTagParts( string $tag ): array
	{
		return $this->extractor->getTagParts( $tag );
	}

	public function parseArray( array $array ): array
	{
		$parsed = [];
		$count  = count( $array );
		$keys   = array_keys( $array );

		if ( ! $count || ! $keys ) {
			return $array;
		}

		$i = 0;
		do {
			$key            = $this->parseString( $keys[ $i ] );
			$parsed[ $key ] = $array[ $keys[ $i ] ];

			if ( is_array( $parsed[ $key ] ) ) {
				$parsed[ $key ] = $this->parseArray( $parsed[ $key ] );
			} elseif ( is_string( $parsed[ $key ] ) ) {
				$parsed[ $key ] = $this->parseString( $parsed[ $key ] );
			}

			$i ++;
		} while ( $i < $count );

		return $parsed;
	}

	public function parseString( string $value ): mixed
	{
		if ( ! $this->hasTag( $value ) ) {
			return $value;
		}

		// Nested tags.
		//$pattern = '/{{\s*([\w.]+)\s*(?:\(\s*"(?:[^"\\\\]|\\\\.)*"\s*\))?\s*}}/';

		if ( $this->extractor->isSingleTag( $value ) ) {
			// Just a single tag. Can return non-string value.
			$tag    = trim( $value, $this->tagStartChar . ' ' . $this->tagEndChar );
			$parsed = $this->parseTag( $tag );

			if ( null === $parsed && ( ! $this->cleanMode || $this->isWhitelisted( $tag ) ) ) {
				return $value;
			}

			return $parsed;
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

			$parsed = $this->parseTag( $part[0] );

			$found = ( null !== $parsed );
			if ( ! $found && $this->cleanMode ) {
				$found = ! $this->isWhitelisted( $part[0] );
			}

			if ( $found ) {
				$part[0]       = (string) $parsed;
				$parts[ $key ] = implode( '', $part );
			} else {
				$parts[ $key ] = $this->tagStartChar . implode( $this->tagEndChar, $part );
			}

			$key ++;
		} while ( $key < $count );

		return implode( '', $parts );
	}

	public function parseTag( string $tag = '' ): mixed
	{
		$tags     = array_map( 'trim', explode( '??', $tag ) );
		$tag      = array_shift( $tags );
		$fallback = implode( ' ?? ', $tags );

		if ( str_contains( $tag, $this->tagSubStartChar ) ) {
			$tag = $this->parseSubTag( $tag );
		}

		// @todo Fix incorrect fallback in case a resource is not set and a whitelist is enabled.
		// Example: {{ row.foo.bar ?? 'test' }} would result in 'test' and therefore doesn't take a whitelist into account.

		$tag = array_map( 'trim', explode( $this->tagFilterChar, $tag ) );

		$value = null;
		$res   = $this->resource;
		$parts = $this->getTagParts( $tag[0] );

		if ( ! $this->strictMode ) {
			// Wildcard tags.
			$first = reset( $parts );
			switch ( $first ) {
				case 'timestamp':
					$value = time();
					$res   = null;
				break;
				case 'storage':
					// Allow fetching a storage.
					// @todo restrict access to storages?
					array_shift( $parts );
					$id_or_ref = array_shift( $parts );

					$storage = StorageModel::get( $id_or_ref );
					if ( ! $storage ) {
						return null;
					}

					$res = new ResourceData( $storage );
				break;
			}
		}

		if ( $res ) {
			$value = $res->get( $parts );
		}

		// In case tags result in new tags.
		if ( $this->recurseMode && $this->hasTag( $value ) ) {
			if ( is_iterable( $value ) ) {
				if ( $value instanceof ResourceData ) {
					$value = $value->get();
				}
				$value = $this->parseArray( (array) $value );
			} elseif ( is_string( $value ) ) {
				$value = $this->parseString( $value );
			}
		}

		// Apply filter.
		if ( isset( $value ) && ! empty( $tag[1] ) ) {

			if ( $value instanceof ResourceData ) {
				$value = $value->get();
			}

			$value = $this->filterTag( $value, $tag[1] );
		}

		if ( ! isset( $value ) && $fallback ) {
			if ( str_starts_with( $fallback, $this->tagEnclosureChar ) && str_ends_with( $fallback, $this->tagEnclosureChar ) ) {
				return trim( $fallback, $this->tagEnclosureChar );
			}
			return $this->parseTag( $fallback );
		}

		return $value;
	}

	protected function parseSubTag( string $tag ): string
	{
		while ( preg_match( '/<\{([^{}]*)\}>/', $tag, $matches ) ) {
			$fullMatch = $matches[0]; // The full `<{ ... }>` match.
			$subTag    = $matches[1]; // Content inside `<{ ... }>`.

			// Recursively parse the sub-tag.
			$parsedSubTag = $this->parseTag( $subTag );

			// Replace in original tag.
			$tag = str_replace( $fullMatch, $parsedSubTag, $tag );
		}

		return $tag;
	}

	protected function filterTag( $value, string $filter ): mixed
	{
		[ $filter, $args ] = $this->resource->parseKeyArgs( $filter );

		$callable = [ $this, 'filter' . ucfirst( $filter ) ];
		if ( ! is_callable( $callable ) ) {
			$callable = is_callable( $filter ) ? $filter : null;
		}

		if ( $callable ) {
			array_unshift( $args, $value );
			$value = call_user_func_array( $callable, $args );
		}

		return $value;
	}
}
