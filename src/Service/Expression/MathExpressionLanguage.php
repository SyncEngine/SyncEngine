<?php

namespace SyncEngine\Service\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Expression language restricted to arithmetic.
 *
 * Only pure math functions are registered. The default `constant()` and `enum()` functions are intentionally
 * not available so expressions cannot read PHP constants or application state.
 */
class MathExpressionLanguage extends ExpressionLanguage
{
	public const FUNCTIONS = [ 'min', 'max', 'abs', 'round', 'floor', 'ceil', 'sqrt', 'pow', 'intdiv', 'fmod' ];

	protected function registerFunctions(): void
	{
		foreach ( self::FUNCTIONS as $function ) {
			$this->addFunction( ExpressionFunction::fromPhp( $function ) );
		}
	}

	/**
	 * Convert data to a structure that supports dot notation in expressions.
	 * Associative arrays become objects (`data.total`), lists stay arrays (`data.lines[0]`).
	 */
	public static function toExpressionValue( mixed $value ): mixed
	{
		if ( $value instanceof \ArrayObject ) {
			$value = $value->getArrayCopy();
		}

		if ( ! is_array( $value ) ) {
			return $value;
		}

		$value = array_map( [ self::class, 'toExpressionValue' ], $value );

		return array_is_list( $value ) ? $value : (object) $value;
	}
}
