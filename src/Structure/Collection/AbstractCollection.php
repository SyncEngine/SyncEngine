<?php

namespace SyncEngine\Structure\Collection;

abstract class AbstractCollection implements CollectionInterface
{
	protected $collection;

	public static function fixFloatOffset( mixed $offset ): mixed
	{
		return is_float( $offset ) ? (string) $offset : $offset;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetExists( mixed $offset ): bool
	{
		return isset( $this->collection[ self::fixFloatOffset( $offset ) ] );
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet( mixed $offset ): mixed
	{
		return $this->collection[ self::fixFloatOffset( $offset ) ] ?? null;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->collection[ self::fixFloatOffset( $offset ) ] = $value;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset( mixed $offset ): void
	{
		unset( $this->collection[ self::fixFloatOffset( $offset ) ] );
	}

	public function current(): mixed
	{
		return current( $this->collection );
	}

	public function next(): void
	{
		next( $this->collection );
	}

	public function key(): mixed
	{
		return key( $this->collection );
	}

	public function valid(): bool
	{
		return null !== $this->key();
	}

	public function rewind(): void
	{
		reset( $this->collection );
	}

	public function count(): int
	{
		return count( $this->collection );
	}
}
