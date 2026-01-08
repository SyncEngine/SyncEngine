<?php

namespace SyncEngine\Service;

use SyncEngine\Structure\Data\ResourceData;

class ConditionsValidator
{
	const OPERATOR_SET                 = '?';
	const OPERATOR_NOT_SET             = '!?';
	const OPERATOR_EMPTY               = '!';
	const OPERATOR_NOT_EMPTY           = '!!';
	const OPERATOR_IN                  = '@'; // '∈'
	const OPERATOR_NOT_IN              = '!@'; // '∉'
	const OPERATOR_IN_STRICT           = '@='; // '∈='
	const OPERATOR_NOT_IN_STRICT       = '!@='; // '∉='
	const OPERATOR_CONTAINS            = ':'; // '∋'
	const OPERATOR_NOT_CONTAINS        = '!:'; // '∌'
	const OPERATOR_CONTAINS_STRICT     = ':='; // '∋='
	const OPERATOR_NOT_CONTAINS_STRICT = '!:='; // '∌='
	const OPERATOR_HAS_KEY             = '#';
	const OPERATOR_NOT_HAS_KEY         = '!#';
	const OPERATOR_LESSER              = '<';
	const OPERATOR_GREATER             = '>';
	const OPERATOR_LESSER_OR_EQUAL     = '<=';
	const OPERATOR_GREATER_OR_EQUAL    = '>=';
	const OPERATOR_EQUAL               = '==';
	const OPERATOR_NOT_EQUAL           = '!=';
	const OPERATOR_EQUAL_STRICT        = '===';
	const OPERATOR_NOT_EQUAL_STRICT    = '!==';
	const OPERATOR_STARTS_WITH         = '^';
	const OPERATOR_NOT_STARTS_WITH     = '!^';
	const OPERATOR_ENDS_WITH           = '$';
	const OPERATOR_NOT_ENDS_WITH       = '!$';
	const OPERATOR_REGEX               = '.*';

	const OPERATORS = [
		self::OPERATOR_SET => self::OPERATOR_SET,
		self::OPERATOR_NOT_SET => self::OPERATOR_NOT_SET,
		self::OPERATOR_EMPTY => self::OPERATOR_EMPTY,
		self::OPERATOR_NOT_EMPTY => self::OPERATOR_NOT_EMPTY,
		self::OPERATOR_IN => self::OPERATOR_IN,
		self::OPERATOR_NOT_IN => self::OPERATOR_NOT_IN,
		self::OPERATOR_IN_STRICT => self::OPERATOR_IN_STRICT,
		self::OPERATOR_NOT_IN_STRICT => self::OPERATOR_NOT_IN_STRICT,
		self::OPERATOR_CONTAINS => self::OPERATOR_CONTAINS,
		self::OPERATOR_NOT_CONTAINS => self::OPERATOR_NOT_CONTAINS,
		self::OPERATOR_CONTAINS_STRICT => self::OPERATOR_CONTAINS_STRICT,
		self::OPERATOR_NOT_CONTAINS_STRICT => self::OPERATOR_NOT_CONTAINS_STRICT,
		self::OPERATOR_HAS_KEY => self::OPERATOR_HAS_KEY,
		self::OPERATOR_NOT_HAS_KEY => self::OPERATOR_NOT_HAS_KEY,
		self::OPERATOR_LESSER => self::OPERATOR_LESSER,
		self::OPERATOR_GREATER => self::OPERATOR_GREATER,
		self::OPERATOR_LESSER_OR_EQUAL => self::OPERATOR_LESSER_OR_EQUAL,
		self::OPERATOR_GREATER_OR_EQUAL => self::OPERATOR_GREATER_OR_EQUAL,
		self::OPERATOR_EQUAL => self::OPERATOR_EQUAL,
		self::OPERATOR_NOT_EQUAL => self::OPERATOR_NOT_EQUAL,
		self::OPERATOR_EQUAL_STRICT => self::OPERATOR_EQUAL_STRICT,
		self::OPERATOR_NOT_EQUAL_STRICT => self::OPERATOR_NOT_EQUAL_STRICT,
		self::OPERATOR_STARTS_WITH => self::OPERATOR_STARTS_WITH,
		self::OPERATOR_NOT_STARTS_WITH => self::OPERATOR_NOT_STARTS_WITH,
		self::OPERATOR_ENDS_WITH => self::OPERATOR_ENDS_WITH,
		self::OPERATOR_NOT_ENDS_WITH => self::OPERATOR_NOT_ENDS_WITH,
		self::OPERATOR_REGEX => self::OPERATOR_REGEX,
	];

	public function getOperator( ?string $operator ): ?string
	{
		if ( isset( self::OPERATORS[ $operator ] ) ) {
			return $operator;
		}

		return match ( $operator ) {
			'set', 'isset', 'defined' => self::OPERATOR_SET,
			'not_set', 'notset', 'undefined' => self::OPERATOR_NOT_SET,
			'empty' => self::OPERATOR_EMPTY,
			'not_empty', 'notempty' => self::OPERATOR_NOT_EMPTY,
			'in' => self::OPERATOR_IN,
			'not_in' => self::OPERATOR_NOT_IN,
			'in_strict' => self::OPERATOR_IN_STRICT,
			'not_in_strict' => self::OPERATOR_NOT_IN_STRICT,
			'contains' => self::OPERATOR_CONTAINS,
			'not_contains' => self::OPERATOR_NOT_CONTAINS,
			'contains_strict' => self::OPERATOR_CONTAINS_STRICT,
			'not_contains_strict' => self::OPERATOR_NOT_CONTAINS_STRICT,
			'has_key', 'haskey' => self::OPERATOR_HAS_KEY,
			'not_has_key', 'nothaskey' => self::OPERATOR_NOT_HAS_KEY,
			'lt', 'lesser' => self::OPERATOR_LESSER,
			'gt', 'greater' => self::OPERATOR_GREATER,
			'le', 'lesser_or_equal' => self::OPERATOR_LESSER_OR_EQUAL,
			'ge', 'greater_or_equal' => self::OPERATOR_GREATER_OR_EQUAL,
			'eq', 'equal' => self::OPERATOR_EQUAL,
			'ne', 'not_equal' => self::OPERATOR_NOT_EQUAL,
			'eqs', 'equal_strict' => self::OPERATOR_EQUAL_STRICT,
			'nes', 'not_equal_strict' => self::OPERATOR_NOT_EQUAL_STRICT,
			'starts_with' => self::OPERATOR_STARTS_WITH,
			'ends_with' => self::OPERATOR_ENDS_WITH,
			'not_starts_with' => self::OPERATOR_NOT_STARTS_WITH,
			'not_ends_with' => self::OPERATOR_NOT_ENDS_WITH,
			'regex' => self::OPERATOR_REGEX,
			default => null,
		};
	}

	/**
	 *  Each item in the conditions list should be either:
	 *  - A condition list array with 'source', 'key', 'compare', and 'operator' keys.
	 *  - A condition assoc array which will be parsed as a list array mentioned above.
	 *  - A condition group array with 'conditions' (array) and 'operator' keys.
	 *
	 * @param  array{ conditions?: array, source: mixed, key: mixed, compare: mixed, operator: string }[]  $conditions
	 * @param  mixed|null  $data
	 * @param  string  $operator  Logical operator to use for the conditions, either 'AND' or 'OR'.
	 *
	 * @return bool
	 */
	public function validate( array $conditions, mixed $data = null, string $operator = 'AND' ): bool
	{
		// @todo Parse conditions? Currently done in Execute Service.
		$any   = 'OR' === strtoupper( $operator ) || '||' === $operator;
		$valid = false;

		if ( ! array_is_list( $conditions ) ) {
			// Assoc style conditions.
			$conditions = array_map( function ( $condition, $key ) {
				$compare  = $condition;
				$operator = null;
				if ( is_iterable( $condition ) && ( isset( $condition['compare'] ) || isset( $condition['operator'] ) ) ) {
					$compare  = $condition['compare'] ?? null;
					$operator = $condition['operator'] ?? null;
					$key      = $condition['key'] ?? $key;
				}
				return [
					'key'      => $key,
					'compare'  => $compare,
					'operator' => $operator,
				];
			}, $conditions, array_keys( $conditions ) );
		}

		foreach ( $conditions as $condition ) {
			if ( ! $condition ) {
				continue;
			}

			$valid = $this->validateRow( $condition, $data );

			if ( ! $valid && ! $any ) {
				return false;
			}
			if ( $valid && $any ) {
				return true;
			}
		}

		return $valid;
	}

	/**
	 * Validate a single row in a condition group.
	 *
	 * The input array should be either:
	 * - A condition array with 'source', 'key', 'compare', and 'operator' keys.
	 * - A condition group array with 'conditions' (array) and 'operator' keys.
	 *
	 * @param  array{
	 *      source?: mixed,
	 *      key?: mixed,
	 *      compare?: mixed,
	 *      operator?: string,
	 *      conditions?: array
	 * }  $row  Description: Either a condition or a group of conditions.
	 * @param  mixed|null  $data
	 *
	 * @return bool
	 */
	protected function validateRow( array $row, mixed $data = null ): bool
	{
		if ( ! empty( $row['conditions'] ) && is_iterable( $row['conditions'] ) ) {
			return $this->validate( $row['conditions'], $data, $row['operator'] ?? 'AND' );
		}

		return $this->validateCondition( $row, $data );
	}

	/**
	 * @param  array{ source: mixed, key: mixed, compare: mixed, operator: string }  $condition
	 * @param  mixed|null  $data
	 *
	 * @return bool
	 */
	protected function validateCondition( array $condition, mixed $data = null ): bool
	{
		/**
		 * In case the SOURCE is generated by a tag (storage for example).
		 * Non-scalar input is always the result of a parsed tag and should therefore be considered as the source data.
		 *
		 * @todo Should we add an extra property "data". Or should we adjust the conditional configs?
		 * @link https://github.com/JoryHogeveen/SyncEngine/issues/91
		 */
		if ( isset( $condition['source'] ) && ( ! self::isEmptyValue( $condition['source'] ) || ! is_scalar( $condition['source'] ) ) ) {
			$data = $condition['source'];
		}

		if ( $data instanceof ResourceData && $data->isEmpty() ) {
			$data = [];
		}

		$key = $condition['key'] ?? '';
		if ( static::isEmptyValue( $key ) ) {

			if ( empty( $condition['operator'] ) ) {
				return true;
			}

			// Direct value comparison.
			$data = [ 'data' => $data ];
			$key  = 'data';
		} elseif ( ! static::isValidKey( $key ) ) {
			/**
			 * In case the KEY is generated by a tag.
			 */
			$data = [ 'data' => $key ];
			$key  = 'data';
		}

		$data = ResourceData::create( $data );

		$compare  = $condition['compare'] ?? null;
		$operator = $this->getOperator( $condition['operator'] ?? null );

		if ( ! $operator ) {
			$operator = match( true ) {
				is_array( $compare ) => self::OPERATOR_IN,
				true === $compare => self::OPERATOR_NOT_EMPTY,
				false === $compare => self::OPERATOR_EMPTY,
				default => self::OPERATOR_EQUAL_STRICT,
			};
		}

		switch ( $operator ) {

			case self::OPERATOR_EQUAL_STRICT:
				return isset( $data[ $key ] ) && $data[ $key ] === $compare;

			case self::OPERATOR_NOT_EQUAL_STRICT:
				return isset( $data[ $key ] ) && $data[ $key ] !== $compare;

			case self::OPERATOR_EQUAL:
				return isset( $data[ $key ] ) && $data[ $key ] == $compare;

			case self::OPERATOR_NOT_EQUAL:
				return isset( $data[ $key ] ) && $data[ $key ] != $compare;

			case self::OPERATOR_SET:
				return isset( $data[ $key ] );

			case self::OPERATOR_NOT_SET:
				return ! isset( $data[ $key ] );

			case self::OPERATOR_EMPTY:
				return empty( $data[ $key ] );

			case self::OPERATOR_NOT_EMPTY:
				return ! empty( $data[ $key ] );

			case self::OPERATOR_IN:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $compare ) ) {
						return in_array( $data[ $key ], $compare );
					}

					return str_contains( $compare, $data[ $key ] );
				}

				return false;

			case self::OPERATOR_NOT_IN:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $compare ) ) {
						return ! in_array( $data[ $key ], $compare );
					}

					return ! str_contains( $compare, $data[ $key ] );
				}

				return true;

			case self::OPERATOR_IN_STRICT:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $compare ) ) {
						return in_array( $data[ $key ], $compare, true );
					}

					return $this->strContainsStrict( $compare, $data[ $key ] );
				}

				return false;

			case self::OPERATOR_NOT_IN_STRICT:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $compare ) ) {
						return ! in_array( $data[ $key ], $compare, true );
					}

					return ! $this->strContainsStrict( $compare, $data[ $key ] );
				}

				return true;

			case self::OPERATOR_CONTAINS:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return in_array( $compare, $data[ $key ] );
					}

					return str_contains( $data[ $key ], $compare );
				}

				return false;

			case self::OPERATOR_NOT_CONTAINS:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return ! in_array( $compare, $data[ $key ] );
					}

					return ! str_contains( $data[ $key ], $compare );
				}

				return true;

			case self::OPERATOR_CONTAINS_STRICT:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return in_array( $compare, $data[ $key ], true );
					}

					return $this->strContainsStrict( $data[ $key ], $compare );
				}

				return false;

			case self::OPERATOR_NOT_CONTAINS_STRICT:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return ! in_array( $compare, $data[ $key ], true );
					}

					return ! $this->strContainsStrict( $data[ $key ], $compare );
				}

				return true;

			case self::OPERATOR_HAS_KEY:
				if ( ! static::isValidKey( $compare ) ) {
					return false;
				}

				// Check value first.
				$value = $data[ $key ] ?? null;
				if ( ! $value ) {
					return false;
				}

				return isset( $value[ $compare ] );

			case self::OPERATOR_NOT_HAS_KEY:
				if ( ! static::isValidKey( $compare ) ) {
					return true;
				}

				// Check value first.
				$value = $data[ $key ] ?? null;
				if ( ! $value ) {
					// @todo Error?
					return true;
				}

				return ! isset( $value[ $compare ] );

			case self::OPERATOR_STARTS_WITH:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return $compare == $data[ $key ][ array_key_first( $data[ $key ] ) ];
					}

					return str_starts_with( $data[ $key ], $compare );
				}

				return false;

			case self::OPERATOR_NOT_STARTS_WITH:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return $compare != $data[ $key ][ array_key_first( $data[ $key ] ) ];
					}

					return ! str_starts_with( $data[ $key ], $compare );
				}

				return true;

			case self::OPERATOR_ENDS_WITH:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return $compare == $data[ $key ][ array_key_last( $data[ $key ] ) ];
					}

					return str_ends_with( $data[ $key ], $compare );
				}

				return false;

			case self::OPERATOR_NOT_ENDS_WITH:
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return $compare != $data[ $key ][ array_key_last( $data[ $key ] ) ];
					}

					return ! str_ends_with( $data[ $key ], $compare );
				}

				return true;

			case self::OPERATOR_LESSER:
				return isset( $data[ $key ] ) && $data[ $key ] < $compare;

			case self::OPERATOR_GREATER:
				return isset( $data[ $key ] ) && $data[ $key ] > $compare;

			case self::OPERATOR_LESSER_OR_EQUAL:
				return isset( $data[ $key ] ) && $data[ $key ] <= $compare;

			case self::OPERATOR_GREATER_OR_EQUAL:
				return isset( $data[ $key ] ) && $data[ $key ] >= $compare;

			case self::OPERATOR_REGEX:
				return isset( $data[ $key ] ) && preg_match( $compare, $data[ $key ] );
		}

		return false;
	}

	public static function isEmptyValue( mixed $value ): bool
	{
		return empty( $value ) && ! is_numeric( $value );
	}

	public static function isValidKey( mixed $value ): bool
	{
		return is_string( $value ) || is_numeric( $value );
	}

	private function strContainsStrict( string $haystack, string $needle ): bool
	{
		// Custom word boundary that allows underscores to be detected as word separators.
		return (bool) preg_match( "~(?:\b|_\K)({$needle})(?=\b|_)~", $haystack );
	}
}
