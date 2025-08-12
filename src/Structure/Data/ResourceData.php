<?php

namespace SyncEngine\Structure\Data;

use SyncEngine\Structure\Data\Interface\RecursiveDataInterface;
use SyncEngine\Structure\Data\Trait\ArrayUtilsTrait;
use SyncEngine\Structure\Data\Trait\RecursiveOffsetTrait;

class ResourceData extends \ArrayObject implements RecursiveDataInterface
{
	use ArrayUtilsTrait;
	use RecursiveOffsetTrait;

	protected object $object;

	public function __construct( object|array $resource = [], int $flags = 0, string $iteratorClass = "ArrayIterator" ) {
		if ( is_object( $resource ) ) {
			$this->object = $resource;
		}
		parent::__construct( $resource, $flags, $iteratorClass );
	}

	public static function create( $resource = [] ): static
	{
		if ( $resource instanceof static ) {
			return $resource;
		}
		return new static( $resource );
	}

	public function has( string|int|array|null $key = null ): bool
	{
		$res = $this->getArrayCopy();

		if ( null === $key ) {
			return ! empty( $res );
		}

		$key = $this->parseKey( $key );

		if ( ! is_array( $key ) ) {
			return isset( $res[ $key ] );
		}

		return null !== $this->get( $key );
	}

	public function exists( string|int|array|null $key = null ): bool
	{
		if ( isset( $this->object ) && ! $this->object instanceof \ArrayAccess ) {
			$resource = $this->object;
		} else {
			$resource = $this->getArrayCopy();
		}

		if ( $this->isKey( $key ) ) {
			return $this->_existsRecursive( $this->parseKey( $key ), $resource ) ?? false;
		}

		return ! empty( $resource );
	}

	public function getByKey( $key, mixed $default = null ): mixed
	{
		if ( isset( $this->object ) && ! $this->object instanceof \ArrayAccess ) {
			return $this->get( $key );
		}

		$resource = $this->getArrayCopy();

		return $resource[ $key ] ?? $default;
	}

	public function setByKey( $value, $key ): static
	{
		if ( isset( $this->object ) && ! $this->object instanceof \ArrayAccess ) {
			return $this->set( $value, $key );
		}

		$resource = $this->getArrayCopy();
		$resource[ $key ] = $value;
		$this->exchangeArray( $resource );

		return $this;
	}

	public function unsetByKey( $key ): static
	{
		if ( isset( $this->object ) && ! $this->object instanceof \ArrayAccess ) {
			return $this->unset( $key );
		}

		$resource = $this->getArrayCopy();
		unset( $resource[ $key ] );
		$this->exchangeArray( $resource );

		return $this;
	}

	/**
	 * @throws \SyncEngine\Exception\InvalidOffsetException
	 *
	 * @param string|int|array $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( $key = null, mixed $default = null ): mixed
	{
		if ( isset( $this->object ) && ! $this->object instanceof \ArrayAccess ) {
			$resource = $this->object;
		} else {
			$resource = $this->getArrayCopy();
		}

		if ( $this->isKey( $key ) ) {
			return $this->_getRecursive( $this->parseKey( $key ), $resource ) ?? $default;
		}

		return $resource;
	}

	/**
	 * @throws \SyncEngine\Exception\InvalidOffsetException
	 *
	 * @param mixed $value
	 * @param string|int|array  $key
	 *
	 * @return $this
	 */
	public function set( $value, $key = null ): static
	{
		if ( isset( $this->object ) && ! $this->object instanceof \ArrayAccess ) {
			$resource = $this->object;
		} else {
			$resource = $this->getArrayCopy();
		}

		if ( $this->isKey( $key ) ) {
			$value = $this->_setRecursive( $value, $this->parseKey( $key ), $resource );
		}

		if ( is_object( $value ) ) {
			$this->object = $value;
		}
		$this->exchangeArray( $value );

		return $this;
	}

	/**
	 * @throws \SyncEngine\Exception\InvalidOffsetException
	 *
	 * @param  string|int|array  $key
	 *
	 * @return $this
	 */
	public function unset( $key ): static
	{
		$resource = $this->getArrayCopy();

		$resource = $this->_unsetRecursive( $this->parseKey( $key ), $resource );

		$this->exchangeArray( $resource );

		return $this;
	}

	public function append( mixed $value ): void
	{
		if ( $value instanceof self ) {
			foreach ( $value as $val ) {
				parent::append( $val );
			}
			return;
		}

		parent::append( $value );
	}

	/**
	 * Insert value only if it does not exist.
	 *
	 * @param  array|\ArrayObject  $data
	 * @param  bool      $recursive
	 *
	 * @return $this
	 */
	public function insert( $data, $recursive = false ): static
	{
		$this->_combineRecursive( $data, $this, $recursive, 'insert' );

		return $this;
	}

	/**
	 * Replace only if new value is not empty.
	 *
	 * @param  array|\ArrayObject  $data
	 * @param  bool      $recursive
	 *
	 * @return $this
	 */
	public function merge( $data, $recursive = false ): static
	{
		$this->_combineRecursive( $data, $this, $recursive, 'merge' );

		return $this;
	}

	/**
	 * Replace with new values.
	 *
	 * @param  array|\ArrayObject  $data
	 * @param  bool      $recursive
	 *
	 * @return $this
	 */
	public function replace( $data, $recursive = false ): static
	{
		$this->_combineRecursive( $data, $this, $recursive, 'replace' );

		return $this;
	}

	/**
	 * // @todo Should ResourceData be immutable?
	 *
	 * @param array|\ArrayObject $data
	 * @param array|\ArrayObject $resource
	 * @param bool $recursive
	 * @param "replace"|"merge"|"insert" $mode
	 *
	 * @return mixed
	 */
	protected function _combineRecursive( $data, $resource, $recursive, $mode = '' ): mixed
	{
		if ( ! is_iterable( $resource ) || ! is_iterable( $data ) ) {
			switch ( $mode ) {
				case 'replace':
					return $data;
				case 'merge':
					if ( isset( $data ) ) {
						return $data;
					}
				break;
			}
			return $resource;
		}

		foreach ( $data as $key => $value ) {
			if ( ! isset( $resource[ $key ] ) ) {
				$resource[ $key ] = $value;
				continue;
			}
			if ( is_iterable( $value ) && $recursive ) {
				$resource[ $key ] = $this->_combineRecursive( $value, $resource[ $key ], $recursive, $mode );
				continue;
			}

			switch ( $mode ) {
				case 'replace':
					$resource[ $key ] = $value;
				break;
				case 'merge':
					if ( is_int( $key ) ) {
						$resource[] = $value;
					} elseif ( isset( $value ) ) {
						$resource[ $key ] = $value;
					}
				break;
				case 'insert':
					if ( is_int( $key ) ) {
						$resource[] = $value;
					}
				break;
			}
		}

		return $resource;
	}

	/**
	 * @param array|\ArrayObject $data
	 *
	 * @return array
	 */
	public function normalize( $data = null ): mixed
	{
		if ( null === $data ) {
			$data = $this->get();
		}

		if ( is_object( $data ) ) {
			if ( method_exists( $data, 'normalize' ) ) {
				$data = $data->normalize();
			} elseif ( $data instanceof \ArrayObject ) {
				$data = $data->getArrayCopy();
			} else {
				// @todo Symfony normalizer?
				$data = get_object_vars( $data );
			}
		}

		if ( is_iterable( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( $value && ( is_iterable( $value ) || is_object( $value ) ) ) {
					$data[ $key ] = $this->normalize( $value );
				}
			}
		}

		return $data;
	}

	public function offsetExists( mixed $key ): bool
	{
		return $this->has( $key );
	}

	public function offsetGet( mixed $key ): mixed
	{
		return $this->get( $key );
	}

	public function offsetSet( mixed $key, mixed $value ): void
	{
		if ( null === $key ) {
			// Append: static[].
			parent::offsetSet( $key, $value );
			return;
		}
		$this->set( $value, $key );
	}

	public function offsetUnset( mixed $key ): void
	{
		$this->unset( $key );
	}
}
