<?php

namespace SyncEngine\Structure\Data;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @template T
 */
class SequenceData implements \ArrayAccess, \Countable, \SeekableIterator
{
	private int $current = 0;
	private array $sequence = [];

	/**
	 * @var string[]
	 */
	private array $refs = [];
	private $initCallback;

	public function __construct( array $sequence = [], ?callable $initCallback = null )
	{
		foreach ( $sequence as $index => $step ) {
			$this->add( $index, $step );
		}

		$this->initCallback = $initCallback;
	}

	public function add( int $index, mixed $step ): static
	{
		$accessor = PropertyAccess::createPropertyAccessor();

		if ( is_array( $step ) || is_object( $step ) ) {
			$ref = $accessor->getValue( $step, '_ref' ) ?: $accessor->getValue( $step, 'ref' ) ?: null;
		}

		$this->refs[ $index ]     = (string) ( $ref ?? $index );
		$this->sequence[ $index ] = $step;

		return $this;
	}

	public function remove( int|string $ref ): static
	{
		$index = $this->getIndex( $ref );

		unset( $this->sequence[ $index ] );
		unset( $this->refs[ $index ] );

		return $this;
	}

	/**
	 * @param  int|string  $ref
	 *
	 * @return T
	 */
	public function init( int|string $ref ): mixed
	{
		$index  = $this->getIndex( $ref );
		$ref    = $this->getRef( $index );
		$config = $this->_get( $index );

		$initialized = $this->initCallback ? call_user_func( $this->initCallback, $config, $ref, $index ) : $config;

		$this->sequence[ $index ] = $initialized;

		return $initialized;
	}

	/**
	 * @param  int|string  $ref
	 *
	 * @return T
	 */
	public function get( int|string $ref ): mixed
	{
		return $this->init( $ref );
	}

	public function _get( int|string $ref ): mixed
	{
		return $this->sequence[ $this->getIndex( $ref ) ] ?? null;
	}

	public function getIndex( int|string $ref ): ?int
	{
		if ( is_int( $ref ) ) {
			return $ref;
		}

		return array_search( $ref, $this->refs, true );
	}

	public function getRefs(): array
	{
		return $this->refs;
	}

	public function getRef( int $offset ): ?string
	{
		return $this->refs[ $offset ] ?? null;
	}

	public function offsetExists( mixed $offset ): bool
	{
		if ( is_int( $offset ) ) {
			return isset( $this->sequence[ $offset ] );
		}

		return in_array( $offset, $this->refs, true );
	}

	public function offsetGet( mixed $offset ): ?array
	{
		return $this->get( $offset );
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->add( $offset, $value );
	}

	public function offsetUnset( mixed $offset ): void
	{
		$this->remove( $offset );
	}

	public function count(): int
	{
		return count( $this->sequence );
	}

	public function normalize(): array
	{
		return $this->sequence;
	}

	public function current(): mixed
	{
		return $this->get( $this->current );
	}

	public function previous(): void
	{
		--$this->current;
	}

	public function next(): void
	{
		++$this->current;
	}

	public function ref(): string
	{
		return $this->refs[ $this->current ];
	}

	public function key(): mixed
	{
		return $this->current;
	}

	public function valid(): bool
	{
		return $this->offsetExists( $this->current );
	}

	public function rewind(): void
	{
		$this->current = 0;
	}

	public function seek( int|string $offset ): void
	{
		$index = $this->getIndex( $offset );

		if ( null === $index ) {
			throw new \OutOfBoundsException("invalid seek position ($offset)");
		}

		$this->current = $index;
	}
}
