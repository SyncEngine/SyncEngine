<?php

namespace SyncEngine\Model\Trait;

trait Conditionals
{
	public function validateConditionals( array $conditionals, array $data ): bool
	{
		// @todo Parse conditionals? Currently done in Execute Service.

		foreach ( $conditionals as $conditional ) {
			if ( ! $conditional ) {
				continue;
			}
			if ( ! $this->validateConditional( $conditional, $data ) ) {
				return false;
			}
		}

		return true;
	}

	protected function validateConditional( array $conditional, $data ): bool
	{
		$valid = true;
		if ( empty( $conditional['key'] ) ) {
			return $valid;
		}

		$key      = $conditional['key'];
		$compare  = $conditional['compare'] ?? null;
		$operator = $conditional['operator'] ?? null;

		if ( ! $operator ) {
			$operator = ( is_array( $compare ) ) ? 'in' : 'default';
		}

		switch ( $operator ) {
			case 'set':
				return isset( $data[ $key ] );
			case 'not_set':
				return ! isset( $data[ $key ] );
			case 'empty':
				return empty( $data[ $key ] );
			case 'not_empty':
				return ! empty( $data[ $key ] );
			case 'in':
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return in_array( $compare, $data[ $key ] );
					}

					return str_contains( $data[ $key ], $compare );
				}

				return false;
			case 'not':
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return ! in_array( $compare, $data[ $key ] );
					}

					return ! str_contains( $data[ $key ], $compare );
				}

				return true;
			case 'in_strict':
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return in_array( $compare, $data[ $key ], true );
					}

					return str_contains( $data[ $key ], $compare );
				}

				return false;
			case 'not_strict':
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return ! in_array( $compare, $data[ $key ], true );
					}

					return ! str_contains( $data[ $key ], $compare );
				}

				return true;
			case 'haskey':
			case 'has_key':
				if ( ! is_string( $compare ) && ! is_numeric( $compare ) ) {
					return false;
				}
				return isset( $data[ $key ][ $compare ] );
			case 'nothaskey':
			case 'not_has_key':
				if ( ! is_string( $compare ) || ! is_numeric( $compare ) ) {
					return true;
				}
				return ! isset( $data[ $key ][ $compare ] );
			case '<':
				return $compare < $data[ $key ];
			case '>':
				return $compare > $data[ $key ];
			case '<=':
				return $compare <= $data[ $key ];
			case '>=':
				return $compare >= $data[ $key ];
			case '!=':
				return $compare != $data[ $key ];
			case '==':
				return $compare == $data[ $key ];
			case '!==':
				return $compare !== $data[ $key ];
			case '===':
			default:
				return $compare === $data[ $key ];
		}
	}
}
