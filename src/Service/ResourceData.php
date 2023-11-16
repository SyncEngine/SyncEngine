<?php

namespace App\Service;

class ResourceData extends \ArrayObject
{
	public string $separator = '.';
	public string $enclose = '"';
	public string $loopKey = '[]';
	public object|array $resource;

	public function __construct( object|array $array = [], int $flags = 0, string $iteratorClass = "ArrayIterator" ) {
		$this->resource = $array;
		parent::__construct( $array, $flags, $iteratorClass );
	}

	public function parseKey( string|int|array $key ): string|int|array
	{
		if ( ! is_string( $key ) || ! str_contains( $key, $this->separator ) ) {
			return $key;
		}

		if ( str_contains( $key, $this->enclose ) ) {
			$result  = [];
			$e       = $this->enclose;
			$s       = $this->separator;
			$pattern = '/' . $e . '([^' . $e . ']*)' . $e . '|' . '([^' . $s . $e . ']+)(?:' . $s . '|$)/';

			preg_replace_callback( $pattern, function ( $matches ) use ( &$result ) {
				$result[] = $matches[1] ?: $matches[2];
			}, $key );

			return $result;
		}

		return explode( $this->separator, $key );
	}

	public function has( string|array $key ): bool
	{
		$res = $this->getArrayCopy();

		$key = $this->parseKey( $key );

		if ( ! is_array( $key ) ) {
			return isset( $res[ $key ] );
		}

		return null !== $this->get( $key );
	}

	public function get( string|int|array $key = null, $default = null ): mixed
	{
		if ( is_object( $this->resource ) && ! $this->resource instanceof \ArrayAccess ) {
			$resource = $this->resource;
			// @todo Normalize object?

			if ( is_string( $key ) ) {
				if ( isset( $this->resource->$key ) ) {
					return $this->resource->$key;
				} elseif ( is_callable( [ $this->resource, 'get' . ucfirst( $key ) ] ) ) {
					return call_user_func( [ $this->resource, 'get' . ucfirst( $key ) ] );
				}
			}
		} else {
			$resource = $this->getArrayCopy();
		}

		if ( null !== $key ) {
			return $this->_getRecursive( $this->parseKey( $key ), $resource ) ?? $default;
		}

		return $resource;
	}

	protected function _getRecursive( $keys, $resource ): mixed
	{
		$current = is_array( $keys ) ? array_shift( $keys ) : $keys;

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

	public function set( $value, string|int|array $key = null ): self
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

		if ( null !== $key ) {
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

	public function unset( $key ): self
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
		$this->set( $value, $key );
	}

	public function offsetUnset( mixed $key ): void
	{
		$this->unset( $key );
	}
}
