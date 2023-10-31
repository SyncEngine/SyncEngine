<?php

namespace App\Service;

class ResourceData extends \ArrayObject
{
	public function parseKey( string|int|array $key ): string|int|array
	{
		if ( ! is_string( $key ) || ! str_contains( $key, '[' ) ) {
			return $key;
		}

		parse_str( $key, $parsed );

		$keys = [];

		do {
			$first = array_key_first( $parsed );
			$keys[] = $first;
			$parsed = $parsed[ $first ];
		} while ( is_array( $parsed ) );

		return $keys;
	}

	public function has( string|array $key ): bool
	{
		$res = $this->getArrayCopy();

		$key = $this->parseKey( $key );

		if ( ! is_array( $key ) ) {
			return isset( $res[ $key ] );
		}

		foreach ( $key as $k ) {
			if ( ! isset( $res[ $k ] ) ) {
				return false;
			}
			$res = $res[ $k ];
		}

		return true;
	}

	public function get( string|int|array $key = null, $default = null ): mixed
	{
		$resource = $this->getArrayCopy();

		if ( $key ) {
			return $this->_getRecursive( $this->parseKey( $key ), $resource ) ?? $default;
		}

		return $resource;
	}

	protected function _getRecursive( $keys, $resource ): mixed
	{
		$current = is_array( $keys ) ? array_shift( $keys ) : $keys;

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

	public function set( $value, string|int|array $key = null ): void
	{
		$resource = $this->getArrayCopy();

		if ( $key ) {
			$value = $this->_setRecursive( $value, $this->parseKey( $key ), $resource );
		}

		$this->exchangeArray( $value );
	}

	protected function _setRecursive( $value, $keys, $resource ): array|object
	{
		if ( is_scalar( $resource ) ) {
			$resource = [];
		}

		$current = is_array( $keys ) ? array_shift( $keys ) : $keys;

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

	public function unset( $key ): void
	{
		$resource = $this->getArrayCopy();

		$resource = $this->_unsetRecursive( $this->parseKey( $key ), $resource );

		$this->exchangeArray( $resource );
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
