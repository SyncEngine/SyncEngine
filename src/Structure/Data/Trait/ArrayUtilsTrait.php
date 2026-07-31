<?php

namespace SyncEngine\Structure\Data\Trait;

trait ArrayUtilsTrait
{
	abstract function getArrayCopy(): array;

	public static function data( mixed $resource = [] ): mixed
	{
		return $resource instanceof \ArrayObject ? $resource->getArrayCopy() : $resource;
	}

	public static function values( array|\ArrayObject $resource = [] ): array
	{
		return array_values( static::data( $resource ) );
	}

	public static function keys( array|\ArrayObject $resource = [] ): array
	{
		return array_keys( static::data( $resource ) );
	}

	public function isEmpty(): bool
	{
		return empty( static::data( $this ) );
	}

	public function hasValues(): bool
	{
		$data = static::data( $this );
		if ( ! is_iterable( $data ) ) {
			return ! empty( $data );
		}

		foreach ( $data as $value ) {
			if ( ! empty( $value ) || is_numeric( $value ) || is_bool( $value ) ) {
				return true;
			}
		}

		return false;
	}

	public function isList(): bool
	{
		return array_is_list( $this->getArrayCopy() );
	}

	public function chunk( int $size, bool $preserve_keys = true ): array
	{
		$chunks = array_chunk( $this->getArrayCopy(), $size, $preserve_keys );

		return array_map( fn( $chunk ) => new static( $chunk ), $chunks );
	}

	public function slice( int $offset, int $length, bool $preserve_keys = true ): static
	{
		return new static( array_slice( $this->getArrayCopy(), $offset, $length, $preserve_keys ) );
	}

	public function filter( ?callable $callback = null, int $mode = 0 ): static
	{
		return new static( array_filter( $this->getArrayCopy(), $callback, $mode ) );
	}

	public function unique( $flags = SORT_REGULAR ): static
	{
		return new static( array_unique( $this->getArrayCopy(), $flags ) );
	}

	public function list(): static
	{
		return new static( static::values( $this ) );
	}

	public function usort( ?callable $callback = null ): static
	{
		$list = $this->get();
		usort( $list, $callback );
		$this->set( $list );

		return $this;
	}
}
