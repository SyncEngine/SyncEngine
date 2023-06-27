<?php

namespace App\Model\Trait;

use App\Component\ExecutionContext;

trait Conditionals
{
	public function check_conditionals( array $conditionals, $data, ExecutionContext $context ): bool
	{
		$valid = true;
		foreach ( $conditionals as $conditional ) {
			if ( ! $this->check_conditional( $conditional, $data, $context ) ) {
				$valid = false;
				break;
			}
		}
		return $valid;
	}

	protected function check_conditional( array $conditional, $data, ExecutionContext $context ): bool
	{

		$valid = true;
		if ( empty( $conditional['key'] ) ) {
			return $valid;
		}

		$key      = $conditional[ 'key' ];
		$compare  = $conditional[ 'compare' ] ?? null;
		$operator = $conditional[ 'operator' ] ?? null;

		if ( ! $operator ) {
			$operator = ( is_array( $compare ) ) ? 'in' : 'default';
		}

		switch ( $operator ) {
			case 'isset':
				return isset( $data[ $key ] );
			case 'notset':
				return ! isset( $data[ $key ] );
			case 'empty':
				return empty( $data[ $key ] );
			case 'notempty':
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
