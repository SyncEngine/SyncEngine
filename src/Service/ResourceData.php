<?php

namespace SyncEngine\Service;

use SyncEngine\Exception\InvalidTagException;

class ResourceData extends \ArrayObject
{
	protected object|array $resource;

	public string $separator = '.';
	public string $enclose = '"';
	public string $loopKey = '[]';

	public function __construct( object|array $resource = [], int $flags = 0, string $iteratorClass = "ArrayIterator" ) {
		$this->resource = $resource;
		parent::__construct( $resource, $flags, $iteratorClass );
	}

	public static function create( $resource = [] ): static
	{
		if ( $resource instanceof static ) {
			return $resource;
		}
		return new static( $resource );
	}

	public function isKey( $key ): bool
	{
		if ( ! empty( $key ) ) {
			return true;
		}
		if ( is_array( $key ) ) {
			return true;
		}
		if ( '0' === (string) $key ) {
			return true;
		}
		return false;
	}

	public function parseKey( string|int|array $key ): string|int|array
	{
		if ( ! is_string( $key ) || ! str_contains( $key, $this->separator ) ) {
			return $key;
		}

		try {
			if ( str_contains( $key, $this->enclose ) ) {
				$result = [];
				$e = $this->enclose;
				$s = $this->separator; // @todo maybe escape dot?
				$pattern = '/' . $e . '([^' . $e . ']*)' . $e . '|' . '([^' . $s . $e . ']+)(?:' . $s . '|$)/';

				preg_replace_callback(
					$pattern,
					function ( $matches ) use ( &$result ) {
						$result[] = ( empty( $matches[1] ) && '0' !== (string) $matches[1] ) ? $matches[2] : $matches[1];
					},
					$key
				);

				return $result;
			}
		} catch ( \Exception $e ) {
			throw new InvalidTagException( 'Invalid tag: ' . $key, $e->getCode(), $e );
		}

		return explode( $this->separator, $key );
	}

	public function parseKeyArgs( string $key ): array
	{
		$args   = array_map( 'trim', explode( '(', $key ) );
		$key    = array_shift( $args );
		$params = array_shift( $args );

		if ( ! empty( $params ) ) {
			$params = rtrim( $params, ')' );
			$params = json_decode( '[' . $params . ']', true );
		}

		return [ $key, $params ?: [] ];
	}

	public function isEmpty(): bool
	{
		return empty( $this->getArrayCopy() );
	}

	public function isList(): bool
	{
		return array_is_list( $this->getArrayCopy() );
	}

	public function has( string|array $key = null ): bool
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

	public function get( string|int|array $key = null, $default = null ): mixed
	{
		if ( is_object( $this->resource ) && ! $this->resource instanceof \ArrayAccess ) {
			return $this->_getFromObject( $key, $default );
		} else {
			$resource = $this->getArrayCopy();
		}

		if ( $this->isKey( $key ) ) {
			return $this->_getRecursive( $this->parseKey( $key ), $resource ) ?? $default;
		}

		return $resource;
	}

	protected function _getFromObject( string|int|array $key = null, $default = null ): mixed
	{
		$resource = $this->resource;
		// @todo Normalize object?

		$value    = $default;
		$key      = $this->parseKey( $key );
		$traverse = null;
		if ( is_array( $key ) ) {
			$traverse = $key;
			$key      = array_shift( $traverse );
		}

		if ( isset( $resource->$key ) ) {
			$value = $resource->$key;
		} else {

			[ $key, $args ] = $this->parseKeyArgs( $key );

			if ( is_callable( [ $resource, 'get' . ucfirst( $key ) ] ) ) {
				$value = call_user_func_array( [ $resource, 'get' . ucfirst( $key ) ], $args );
			}
		}

		if ( $traverse ) {
			$value = ( new self( $value ) )->get( $traverse );
		}

		return $value;
	}

	protected function _getRecursive( $keys, $resource ): mixed
	{
		$current = is_array( $keys ) ? array_shift( $keys ) : $keys;

		/*if ( ! is_array( $current ) ) {
			// @todo find cause.
			return null; // Invalid key.
		}*/

		// Loop structure.
		if ( $this->loopKey === $current ) {
			if ( ! is_iterable( $resource ) ) {
				return null;
			}
			$return = [];
			if ( ! $resource instanceof self ) {
				$resource = new self( $resource );
			}
			foreach ( $resource as $key => $value ) {
				if ( is_scalar( $value ) ) {
					continue;
				}
				if ( ! $value instanceof self ) {
					$value = new self( $value );
				}
				$return[ $key ] = $value->get( $keys );
			}
			return $return;
		}

		if ( is_object( $resource ) && ! $resource instanceof \ArrayAccess ) {
			// @todo Normalize object?
			if ( isset( $resource->$current ) ) {
				$value = $resource->$current;
			} elseif ( is_callable( [ $resource, 'get' . ucfirst( $current ) ] ) ) {
				$value = call_user_func( [ $resource, 'get' . ucfirst( $current ) ] );
			}
		} elseif ( isset( $resource[ $current ] ) ) {
			$value = $resource[ $current ];
		}

		if ( ! isset( $value ) ) {
			return null;
		}

		if ( $keys && is_array( $keys ) ) {
			$value = $this->_getRecursive( $keys, $value );
		}

		return $value;
	}

	public function set( $value, string|int|array $key = null ): static
	{
		if ( is_object( $this->resource ) && ! $this->resource instanceof \ArrayAccess ) {
			$resource = $this->resource;
			// @todo Normalize object?

			if ( is_string( $key ) ) {
				if ( isset( $resource->$key ) ) {
					$resource->$key = $value;
				} elseif ( is_callable( [ $resource, 'set' . ucfirst( $key ) ] ) ) {
					call_user_func( [ $resource, 'set' . ucfirst( $key ) ], $value );
				}
			}
		} else {
			$resource = $this->getArrayCopy();
		}

		if ( $this->isKey( $key ) ) {
			$value = $this->_setRecursive( $value, $this->parseKey( $key ), $resource );
		}

		$this->exchangeArray( $value );

		return $this;
	}

	protected function _setRecursive( $value, $keys, $resource ): array|object
	{
		if ( is_scalar( $resource ) ) {
			$resource = [];
		}

		$current = is_array( $keys ) ? array_shift( $keys ) : $keys;

		// Loop structure.
		if ( $this->loopKey === $current ) {
			if ( ! $resource instanceof self ) {
				$resource = new self( $resource );
			}
			foreach ( $resource as $key => $item ) {
				$return_instance = true;
				if ( ! $item instanceof self ) {
					$return_instance = false;
					$item = new self( is_scalar( $item ) ? [] : $item );
				}
				$item->set( $value, $keys );
				$resource->set( $return_instance ? $item : $item->getArrayCopy(), $key );
			}
			return $resource->getArrayCopy();
		}

		if ( ! $keys || ! is_array( $keys ) ) {
			if ( is_object( $resource ) && ! $resource instanceof \ArrayAccess ) {
				// @todo Normalize object?
				if ( isset( $resource->$current ) ) {
					$resource->$current = $value;
				} elseif ( is_callable( [ $resource, 'set' . ucfirst( $current ) ] ) ) {
					call_user_func( [ $resource, 'set' . ucfirst( $current ) ], $value );
				}
			} elseif ( is_array( $resource ) ) {
				$resource[ $current ] = $value;
			}
		} else {
			if ( is_object( $resource ) && ! $resource instanceof \ArrayAccess ) {
				// @todo Normalize object?
				if ( isset( $resource->$current ) ) {
					$resource->$current = $this->_setRecursive( $value, $keys, $resource->$current );
				} elseif ( is_callable( [ $resource, 'set' . ucfirst( $current ) ] ) ) {
					call_user_func(
						[ $resource, 'set' . ucfirst( $current ) ],
						$this->_setRecursive( $value, $keys, $this->_getRecursive( $current, $resource ) )
					);
				}
			} elseif ( is_array( $resource ) ) {
				$resource[ $current ] = $this->_setRecursive( $value, $keys, $resource[ $current ] ?? [] );
			}
		}

		return $resource;
	}

	public function unset( $key ): static
	{
		$resource = $this->getArrayCopy();

		$resource = $this->_unsetRecursive( $this->parseKey( $key ), $resource );

		$this->exchangeArray( $resource );

		return $this;
	}

	protected function _unsetRecursive( $keys, $resource ): array|object
	{
		if ( ! is_array( $keys ) ) {
			unset( $resource[ $keys ] );
		} else {
			$current = array_shift( $keys );
			if ( isset( $resource[ $current ] ) ) {
				if ( $keys ) {
					$resource[ $current ] = $this->_unsetRecursive( $keys, $resource[ $current ] );
				} else {
					// Last item.
					unset( $resource[ $current ] );
				}
			}
		}

		return $resource;
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
	 * Insert value only if it does not exists.
	 *
	 * @param  iterable  $data
	 * @param  bool      $recursive
	 *
	 * @return $this
	 */
	public function insert( iterable $data, $recursive = false ): static
	{
		$this->_combineRecursive( $data, $this, $recursive, 'insert' );

		return $this;
	}

	/**
	 * Replace only if new value is not empty.
	 *
	 * @param  iterable  $data
	 * @param  bool      $recursive
	 *
	 * @return $this
	 */
	public function merge( iterable $data, $recursive = false ): static
	{
		$this->_combineRecursive( $data, $this, $recursive, 'merge' );

		return $this;
	}

	/**
	 * Replace with new values.
	 *
	 * @param  iterable  $data
	 * @param  bool      $recursive
	 *
	 * @return $this
	 */
	public function replace( iterable $data, $recursive = false ): static
	{
		$this->_combineRecursive( $data, $this, $recursive, 'replace' );

		return $this;
	}

	/**
	 * @param array|\ArrayObject $data
	 * @param array|\ArrayObject $resource
	 * @param bool $recursive
	 * @param "replace"|"merge"|"insert" $mode
	 *
	 * @return mixed
	 */
	protected function _combineRecursive( $data, $resource, $recursive, $mode = '' ): mixed
	{
		if ( ! is_iterable( $resource ) ) {
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
					if ( isset( $value ) ) {
						$resource[ $key ] = $value;
					}
				break;
				case 'insert':
					// Do nothing.
				break;
			}
		}

		return $resource;
	}

	/**
	 * @param  int   $size
	 * @param  bool  $preserve_keys
	 *
	 * @return static[]
	 */
	public function chunk( int $size, $preserve_keys = true ): array
	{
		$chunks = array_chunk( $this->get(), $size, $preserve_keys );
		return array_map( function( $chunk ) { return new static( $chunk ); }, $chunks );
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
		return new static( array_slice( $this->get(), $offset, $length, $preserve_keys ) );
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
		} elseif ( is_object( $data ) ) {
			if ( $data instanceof self ) {
				$data = $data->get();
			} elseif ( $data instanceof \ArrayObject ) {
				$data = $data->getArrayCopy();
			} else {
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
			// Append.
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
