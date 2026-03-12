<?php

namespace SyncEngine\Structure\Data;

use Iterator;
use LogicException;

readonly class IterationData implements \ArrayAccess
{
	protected function __construct(
		public ?int  $index = null,    // 0-based
		public ?int  $current = null,  // 1-based
		public ?int  $size = null,
		public ?int  $limit = null,
		public ?int  $offset = null,
		public ?bool $isFirst = null,
		public ?bool $isLast = null,
	) {}

	public static function create(
		?int $index = null,
		?int $current = null,
		?int $size = null,
		?int $limit = null,
		?int $offset = null,
	): static {
		// Auto-calculate index/current if one is missing
		if ($index === null && $current !== null) {
			$index = $current - 1; // current = index + 1
		} elseif ($current === null && $index !== null) {
			$current = $index + 1; // index = current - 1
		} else {
			$index ??= 0;
			$current = $index + 1;
		}

		return new self(
			index: $index,
			current: $current,
			size: $size,
			limit: $limit,
			offset: $offset,
			isFirst: ( $offset !== null ) ? $index === $offset : null,
			isLast: ($size !== null)
				? $index >= min($offset + $limit, $size) - 1
				: null // Unknown if size is unknown,
		);
	}

	// Factory method for convenience
	public static function fromIterator(
		Iterator $iterator,
		int $size,
		int $limit,
		int $offset
	): static {
		$index = $iterator->key();
		return static::create(
			index: $index,
			current: $index + 1,
			size: $size,
			limit: $limit,
			offset: $offset,
		);
	}

	public static function fromArray( object|array $resource ): static
	{
		return static::create( ...$resource );
	}

	public function getCurrent(): ?int
	{
		return $this->current ?? null;
	}

	public function getIndex(): ?int
	{
		return $this->index ?? null;
	}

	public function getSize(): ?int
	{
		return $this->size ?? null;
	}

	public function getLimit(): ?int
	{
		return $this->limit ?? null;
	}

	public function getOffset(): ?int
	{
		return $this->offset ?? null;
	}

	public function offsetExists( mixed $offset ): bool
	{
		return property_exists( $this, $offset ) || method_exists( $this, 'get' . ucfirst( $offset ) );
	}

	public function offsetGet( mixed $offset ): mixed
	{
		$method = 'get' . ucfirst( $offset );
		if ( method_exists( $this, $method ) ) {
			return $this->$method();
		}
		return $this->{$offset};
	}

	public function offsetSet( mixed $offset, mixed $value ): void {
		throw new LogicException( 'IterationState is immutable.' );
	}

	public function offsetUnset( mixed $offset ): void {
		throw new LogicException( 'IterationState is immutable.' );
	}
}
