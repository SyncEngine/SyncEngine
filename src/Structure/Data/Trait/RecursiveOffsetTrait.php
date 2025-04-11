<?php

namespace SyncEngine\Structure\Data\Trait;

use SyncEngine\Exception\InvalidOffsetException;
use SyncEngine\Structure\Collection\AbstractCollection;

/**
 * @internal
 */
trait RecursiveOffsetTrait
{
	public string $separator = '.';
	public string $enclose = '"';
	public string $loopKey = '[]';
	public string $argWrap = '()';
	public bool $encloseBrackets = false;

	abstract function get( $key = null );
	abstract function set( $value, $key = null );
	abstract function unset( $key );

	public function isKey( mixed $key ): bool
	{
		// @todo General class for type/var valitation.
		// @see Conditions::isValidKey()
		return ! empty( $key ) || is_array( $key ) || (string) $key === '0';
	}

	protected function parseKeyString( string $input ): array
	{
		$result    = [];
		$current   = '';
		$inQuote   = false;
		$inBracket = false;
		$escape    = false;

		$len = strlen( $input );
		for ( $i = 0; $i < $len; $i ++ ) {
			$char = $input[ $i ];
			$next = $input[ $i + 1 ] ?? null;

			// Handle escape characters
			if ( $escape ) {
				$current .= $char;
				$escape  = false;
				continue;
			}

			// Handle escape sequences for quotes or brackets
			if ( $char === '\\' ) {
				$escape = true;
				continue;
			}

			// Check for quote marks
			if ( $char === $this->enclose && ! $inBracket ) {
				if ( $inQuote ) {
					if ( $next === $this->separator || null === $next ) {
						// Close the quote and push the current value
						$result[] = $current;
						$current  = '';
						$inQuote  = false;
						continue;
					}
				} elseif ( ! strlen( $current ) > 0 ) {
					// Open a quote
					$inQuote = true;
					continue;
				}
			}

			if ( $this->encloseBrackets ) {
				// Check for bracket marks
				if ( $char === '[' && ! $inQuote ) {
					if ( $next === ']' ) {
						$i ++;
						$result[] = '[]';
						continue;
					}
					if ( strlen( $current ) > 0 ) {
						$result[] = $current;
						$current  = '';
					}
					$inBracket = true;
					continue;
				}

				if ( $char === ']' && ! $inQuote ) {
					$inBracket = false;
					$result[]  = $current;
					$current   = '';
					continue;
				}
			}

			// Handle dot as separator
			if ( $char === $this->separator && ! $inQuote && ! $inBracket ) {
				if ( strlen( $current ) > 0 ) {
					$result[] = $current;
					$current  = '';
				}
				continue;
			}

			// Add the character to the current segment
			$current .= $char;
		}

		// If there’s any leftover segment after the loop, add it
		if ( strlen( $current ) > 0 ) {
			$result[] = $current;
		}

		return $result;
	}

	public function checkBrackets( $string )
	{
		return $this->encloseBrackets && str_contains( $string, '[' );
	}

	public function isEnclosed( mixed $key ): bool
	{
		return str_starts_with( $key, $this->enclose ) && str_ends_with( $key, $this->enclose );
	}

	public function parseKey( string|int|array $key ): string|int|array
	{
		$s = $this->separator; // @todo maybe escape dot?
		$e = $this->enclose;

		if ( is_string( $key ) && $this->isEnclosed( $key ) ) {
			return substr( $key, strlen( $this->enclose ), -strlen( $this->enclose ) );
		}

		if ( ! is_string( $key ) || ( ! str_contains( $key, $s ) && ! $this->checkBrackets( $key ) ) ) {
			return AbstractCollection::fixFloatOffset( $key );
		}

		try {
			if ( str_contains( $key, $e ) || $this->checkBrackets( $key ) ) {
				return $this->parseKeyString( $key );
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

	protected function _existsRecursive( $keys, $resource ): bool
	{
		if ( is_array( $keys ) ) {
			$key = array_pop( $keys );

			$resource = $this->_getRecursive( $keys, $resource );
		} else {
			$key = $keys;
		}

		if ( null !== $this->_getRecursive( $key, $resource ) ) {
			return true;
		}

		if ( is_object( $resource ) ) {
			if ( is_callable( [ $resource, 'get' . ucfirst( $key ) ] ) ) {
				return true;
			}
			$resource = get_object_vars( $resource );
		}

		if ( is_array( $resource ) ) {
			return array_key_exists( $key, $resource );
		}

		return false;
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
			} else {

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
					$item            = new self( is_scalar( $item ) ? [] : $item );
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

			if ( $keys ) {
				if ( is_object( $resource ) && ! $resource instanceof \ArrayAccess ) {
					$this->_setRecursive( $this->_unsetRecursive( $keys, $this->get( $current, $resource ) ), $keys, $resource );
				} elseif ( is_iterable( $resource ) ) {
					$resource[ $current ] = $this->_unsetRecursive( $keys, $resource[ $current ] );
				}
			} else {
				// Last item.
				if ( is_object( $resource ) && ! $resource instanceof \ArrayAccess ) {
					if ( isset( $resource->$current ) ) {
						$resource->$current = null;
					} elseif ( is_callable( [ $resource, 'set' . ucfirst( $current ) ] ) ) {
						call_user_func( [ $resource, 'set' . ucfirst( $current ) ], null );
					}
				} elseif ( is_iterable( $resource ) ) {
					unset( $resource[ $current ] );
				}
			}
		}

		return $resource;
	}
}
