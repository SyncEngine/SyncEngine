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
			if ( is_array( $key ) ) {
				$return = $this->resource;

				foreach ( $key as $k ) {
					if ( isset( $return[ $k ] ) ) {
						$return = $return[ $k ];
						continue;
					}
					$return = $default;
					break;
				}

				return $return;
			}

			return $this->resource[ $key ] ?? $default;
		}

		return $this->resource;
	}

	public function set( $value, string|int|array $key = null ): array|object
	{
		if ( $key ) {
			if ( is_array( $key ) ) {
				$key = array_reverse( $key );
				$set = $value;

				foreach ( $key as $k ) {
					$set = [ $k => $set ];
				}

				$this->resource = array_replace_recursive( $this->resource, $set );
			} else {
				$this->resource[ $key ] = $value;
			}
		} else {
			$this->resource = $value;
		}

		return $this->resource;
	}

	public function unset( $key ): array|object
	{
		$this->resource = $this->_unset( $key, $this->resource );
		return $this->resource;
	}

	protected function _unset( $key, $resource ): array|object
	{
		if ( ! is_array( $key ) ) {
			unset( $resource[ $key ] );
		} else {
			$current = array_shift( $key );
			if ( $key ) {
				$resource[ $current ] = $this->_unset( $key, $resource[ $current ] );
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
