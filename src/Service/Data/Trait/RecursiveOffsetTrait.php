<?php

namespace SyncEngine\Service\Data\Trait;

use SyncEngine\Exception\InvalidOffsetException;

/**
 * @internal
 */
trait RecursiveOffsetTrait
{
	public string $separator = '.';
	public string $enclose = '"';
	public string $loopKey = '[]';
	public string $argWrap = '()';

	abstract function get( $key = null );
	abstract function set( $value, $key = null );
	abstract function unset( $key );

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
			throw new InvalidOffsetException( 'Invalid tag: ' . $key, $e->getCode(), $e );
		}

		return explode( $this->separator, $key );
	}

	public function parseKeyArgs( string $key ): array
	{
		$args   = array_map( 'trim', explode( $this->argWrap[0], $key ) );
		$key    = array_shift( $args );
		$params = array_shift( $args );

		if ( ! empty( $params ) ) {
			$params = rtrim( $params, $this->argWrap );
			$params = json_decode( '[' . $params . ']', true );
		}

		return [ $key, $params ?: [] ];
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
			} else{

				[ $current, $args ] = $this->parseKeyArgs( $current );

				if ( is_callable( [ $resource, 'get' . ucfirst( $current ) ] ) ) {
					$value = call_user_func( [ $resource, 'get' . ucfirst( $current ) ], $args );
				}
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
				$resource->set( $return_instance ? $item : $item->get(), $key );
			}
			return $resource->get();
		}

		if ( ! $keys || ! is_array( $keys ) ) {
			if ( is_object( $resource ) && ! $resource instanceof \ArrayAccess ) {
				// @todo Normalize object?
				if ( isset( $resource->$current ) ) {
					$resource->$current = $value;
				} elseif ( is_callable( [ $resource, 'set' . ucfirst( $current ) ] ) ) {
					call_user_func( [ $resource, 'set' . ucfirst( $current ) ], $value );
				}
			} elseif ( is_iterable( $resource ) ) {
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
			} elseif ( is_iterable( $resource ) ) {
				$resource[ $current ] = $this->_setRecursive( $value, $keys, $resource[ $current ] ?? [] );
			}
		}

		return $resource;
	}

	protected function _unsetRecursive( $keys, $resource ): array|object
	{
		if ( ! is_array( $keys ) ) {
			unset( $resource[ $keys ] );
		} else {
			$current = array_shift( $keys );
			if ( $keys && isset( $resource[ $current ] ) ) {
				$resource[ $current ] = $this->_unsetRecursive( $keys, $resource[ $current ] );
			} else {
				// Last item.
				if ( is_array( $resource ) || $resource instanceof \ArrayAccess ) {
					unset( $resource[ $current ] );
				}
			}
		}

		return $resource;
	}
}
