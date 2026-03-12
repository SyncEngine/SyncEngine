<?php

namespace SyncEngine\Service\Tag;

use SyncEngine\Model\StorageModel;
use SyncEngine\Service\Generator\Timestamp;
use SyncEngine\Service\Tag\Cleaner\CleanerInterface;
use SyncEngine\Structure\Data\ResourceData;

class TagParser
{
	use TagFilter;

	protected ResourceData $resource;
	protected TagExtractor $extractor;
	protected bool $recurseMode;
	protected bool $strictMode;
	protected bool $cleanMode;
	protected CleanerInterface $cleaner;
	public string $tagStartChar = '{{';
	public string $tagEndChar = '}}';
	public string $tagEnclosureChar = '"';
	public string $tagFilterChar = '|';
	public string $tagSubStartChar = '<{';
	public string $tagSubEndChar = '}>';

	public function __construct(
		array|object $resource = [],
		bool $clean = true,
		bool $strict = false,
		bool $recurse = true,
	) {
		if ( ! $resource instanceof ResourceData ) {
			$resource = new ResourceData( $resource );
		}
		$this->resource    = $resource;
		$this->strictMode  = $strict;
		$this->recurseMode = $recurse;

		$this->setCleanMode( $clean );

		$this->extractor = new TagExtractor( $resource );
	}

	public function setCleanMode( bool|CleanerInterface $mode ): self
	{
		if ( $mode instanceof CleanerInterface ) {
			$this->cleaner = $mode;
			$mode = true;
		}

		$this->cleanMode = $mode;

		return $this;
	}

	public function setCleaner( CleanerInterface $cleaner ): self
	{
		$this->cleaner = $cleaner;

		$this->setCleanMode( true );

		return $this;
	}

	public function shouldClean( string $tag ): bool
	{
		if ( ! $this->cleanMode || empty( $this->cleaner ) ) {
			return $this->cleanMode;
		}

		return $this->cleaner->shouldClean( $tag );
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
			$tag    = $this->extractor->trimTag( $value );
			$parsed = $this->parseTag( $tag );

			if ( null === $parsed && ! $this->shouldClean( $tag ) ) {
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
			if ( ! $found ) {
				$found = $this->shouldClean( $part[0] );
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
		if ( str_contains( $tag, $this->tagSubStartChar ) ) {
			$tag = $this->parseSubTag( $tag );
		}

		$tags     = array_map( 'trim', explode( '??', $tag ) );
		$tag      = array_shift( $tags );
		$fallback = implode( ' ?? ', $tags );

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
					$value = ( new Timestamp() )->generate(
						(string) ( $parts[1] ?? '' ),
						(string) ( $parts[2] ?? '' ),
					);
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
