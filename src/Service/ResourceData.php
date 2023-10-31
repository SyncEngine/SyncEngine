<?php

namespace App\Service;

class ResourceData implements \ArrayAccess
{
	public function __construct(
		public array|object $resource = [],
	) {}

	public function has( string|array $key ): bool
	{
		$res = $this->resource;

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

	public function get( string|int|array $key = null, $default = null ): array|object
	{
		if ( $key ) {
			return $this->_getRecursive( $key, $this->resource ) ?? $default;
		}

		return $this->resource;
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

	public function set( $value, string|int|array $key = null ): array|object
	{
		if ( $key ) {
			$this->resource = $this->_setRecursive( $value, $key, $this->resource );
		} else {
			$this->resource = $value;
		}

		return $this->resource;
	}

	protected function _setRecursive( $value, $keys, $resource ): array|object
	{
		if ( ! is_array( $keys ) ) {
			if ( is_object( $resource ) && ! $resource instanceof \ArrayAccess ) {
				// @todo Normalize object?
				if ( isset( $resource->$keys ) ) {
					$resource->$keys = $value;
				} elseif ( is_callable( [ $resource, 'set' . ucfirst( $keys ) ] ) ) {
					call_user_func_array( [ $resource, 'set' . ucfirst( $keys ) ], $value );
				}
			} elseif ( is_array( $resource ) ) {
				$resource[ $keys ] = $value;
			}
		} else {
			$current = array_shift( $keys );

			if ( is_object( $resource ) && ! $resource instanceof \ArrayAccess ) {
				// @todo Normalize object?
				if ( isset( $resource->$current ) ) {
					$resource->$current = $this->_setRecursive( $value, $keys, $resource->$current );
				} elseif ( is_callable( [ $resource, 'set' . ucfirst( $current ) ] ) ) {
					// @todo How to implement this.
					call_user_func_array( [ $resource, 'set' . ucfirst( $current ) ], $value );
				}
			} elseif ( is_array( $resource ) ) {
				$resource[ $current ] = $this->_setRecursive( $value, $keys, $resource[ $current ] );
			}
		}

		return $resource;
	}

	public function unset( $key ): array|object
	{
		$this->resource = $this->_unsetRecursive( $key, $this->resource );
		return $this->resource;
	}

	protected function _unsetRecursive( $keys, $resource ): array|object
	{
		if ( ! is_array( $keys ) ) {
			unset( $resource[ $keys ] );
		} else {
			$current = array_shift( $keys );
			if ( $keys ) {
				$resource[ $current ] = $this->_unsetRecursive( $keys, $resource[ $current ] );
			} else {
				// Last item.
				unset( $resource[ $current ] );
			}
		}

		return $resource;
	}

	public function offsetExists( mixed $offset ): bool
	{
		return $this->has( $offset );
	}

	public function offsetGet( mixed $offset ): mixed
	{
		return $this->get( $offset );
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->set( $value, $offset );
	}

	public function offsetUnset( mixed $offset ): void
	{
		$this->unset( $offset );
	}
}
