<?php

namespace SyncEngine\Structure\Data;

use SyncEngine\Structure\Collection\AbstractCollection;
use Traversable;

class MapData implements \ArrayAccess, \Countable, \IteratorAggregate
{
	private array $map = [];

	public function add( string $source, string $target ): static
	{
		if ( ! isset( $this->map[ $source ] ) ) {
			$this->map[ $source ] = [];
		}

		if ( ! in_array( $target, $this->map[ $source ] ) ) {
			$this->map[ $source ][] = $target;
		}

		return $this;
	}

	public function getSources(): array
	{
		return array_keys( $this->getMap() );
	}

	public function getTargets(): array
	{
		$targets = [];
		foreach ( $this->getMap() as $target ) {
			$targets = array_merge( $targets, $target );
		}
		return $targets;
	}

	public function getTarget( string $source ): array
	{
		return $this->map[ $source ] ?? [];
	}

	public function getFirstTarget( string $source ): mixed
	{
		return ! empty( $this->map[ $source ] ) ? reset( $this->map[ $source ] ) : null;
	}

	public function getLastTarget( string $source ): mixed
	{
		return ! empty( $this->map[ $source ] ) ? end( $this->map[ $source ] ) : null;
	}

	public function getMap(): array
	{
		return $this->map;
	}

	public function getIterator(): Traversable
	{
		foreach ( $this->map as $source => $targets ) {
			foreach ( (array) $targets as $target ) {
				yield $source => $target;
			}
		}
	}

	public function offsetExists( mixed $offset ): bool
	{
		return isset( $this->map[ AbstractCollection::fixFloatOffset( $offset ) ] );
	}

	public function offsetGet( mixed $offset ): array
	{
		return $this->map[ AbstractCollection::fixFloatOffset( $offset ) ] ?? [];
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->map[ AbstractCollection::fixFloatOffset( $offset ) ] = (array) $value;
	}

	public function offsetUnset( mixed $offset ): void
	{
		unset( $this->map[ AbstractCollection::fixFloatOffset( $offset ) ] );
	}

	public function count(): int
	{
		return count( $this->map );
	}
}
