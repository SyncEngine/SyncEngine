<?php

namespace SyncEngine\Service\Data\Trait;

trait ArrayUtilsTrait
{
	abstract function getArrayCopy(): array;

	public static function data( mixed $resource = [] ): mixed
	{
		return $resource instanceof \ArrayObject ? $resource->getArrayCopy() : $resource;
	}

	public static function values( array|\ArrayObject $resource = [] ): array
	{
		return is_array( $resource ) ? array_values( $resource ) : array_values( $resource->getArrayCopy() );
	}

	public static function keys( array|\ArrayObject $resource = [] ): array
	{
		return is_array( $resource ) ? array_keys( $resource ) : array_keys( $resource->getArrayCopy() );
	}

	public function isList(): bool
	{
		return array_is_list( $this->getArrayCopy() );
	}

	/**
	 * @param  int   $size
	 * @param  bool  $preserve_keys
	 *
	 * @return static[]
	 */
	public function chunk( int $size, $preserve_keys = true ): array
	{
		$chunks = array_chunk( $this->getArrayCopy(), $size, $preserve_keys );

		return array_map( fn( $chunk ) => new static( $chunk ), $chunks );
	}

	/**
	 * @param  int   $offset
	 * @param  int   $length
	 * @param  bool  $preserve_keys
	 *
	 * @return static
	 */
	public function slice( int $offset, int $length, $preserve_keys = true ): static
	{
		return new static( array_slice( $this->getArrayCopy(), $offset, $length, $preserve_keys ) );
	}

	/**
	 * @param  callable|null  $callback
	 * @param  int            $mode
	 *
	 * @return static
	 */
	public function filter( ?callable $callback = null, int $mode = 0 ): static
	{
		return new static( array_filter( $this->getArrayCopy(), $callback, $mode ) );
	}

	/**
	 * @param  int  $flags
	 *
	 * @return $this
	 */
	public function unique( $flags = SORT_REGULAR ): static
	{
		return new static( array_unique( $this->getArrayCopy(), $flags ) );
	}

	/**
	 * @return $this
	 */
	public function list(): static
	{
		return new static( static::values( $this ) );
	}
}
