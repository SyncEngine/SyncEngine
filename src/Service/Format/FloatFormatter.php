<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

class FloatFormatter implements FormatInterface
{
	public const PRECISION = 'precision';
	public const MODE = 'mode';

	private array $defaultContext;

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = $defaultContext;
	}

	private function _parseContext( $context ): array
	{
		if ( isset( $context[ static::MODE ] ) && ! is_int( $context[ static::MODE ] ) ) {
			if ( is_numeric( $context[ static::MODE ] ) ) {
				$context[ static::MODE ] = (int) $context[ static::MODE ];
			} else {
				switch ( $context[ static::MODE ] ) {
					case 'up':
					case 'half_up':
					case 'round_half_up':
						$context[ static::MODE ] = PHP_ROUND_HALF_UP;
					break;
					case 'down':
					case 'half_down':
					case 'round_half_down':
						$context[ static::MODE ] = PHP_ROUND_HALF_DOWN;
					break;
					case 'even':
					case 'half_even':
					case 'round_half_even':
						$context[ static::MODE ] = PHP_ROUND_HALF_EVEN;
					break;
					case 'odd':
					case 'half_odd':
					case 'round_half_odd':
						$context[ static::MODE ] = PHP_ROUND_HALF_ODD;
					break;
					default:
						// Invalid.
						unset( $context[ static::MODE ] );
					break;
				}
			}
		}

		return $context;
	}

	/**
	 * @param  mixed  $var
	 * @param  array  $context
	 *
	 * @return float
	 */
	protected function _format( $var, array $context = [] )
	{
		$context = $this->_parseContext( $context ?: $this->defaultContext );

		if ( ! is_numeric( $var ) ) {
			$var = $this->sanitize( $var );
		}

		$precision = $context[ static::PRECISION ] ?? 0;
		$mode      = $context[ static::MODE ] ?? PHP_ROUND_HALF_UP;

		return round( (float) $var, (int) $precision, (int) $mode );
	}

	protected function sanitize( $var )
	{
		$var = filter_var(
			$var,
			FILTER_SANITIZE_NUMBER_FLOAT,
			FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND
		);

		// Replace all with dots.
		$var = str_replace( [ ',', '.', ' ' ], '.', $var );

		$decimals = null;
		if ( str_contains( $var, '.' ) ) {
			$var      = explode( '.', $var );
			$decimals = array_pop( $var );
			$var      = implode( '', $var );
		}

		return (float) $var . ( $decimals ? '.' . $decimals : '' );
	}

	/**
	 * @param  mixed  $var
	 *
	 * @return mixed
	 */
	public function format( mixed $var ): mixed
	{
		return $this->_format( $var );
	}

	/**
	 * @param  mixed                 $var
	 * @param  FormatInterface|null  $fromFormat
	 *
	 * @return mixed
	 */
	public function convert( mixed $var, ?FormatInterface $fromFormat = null ): mixed
	{
		if ( $fromFormat instanceof FormatInterface ) {
			$var = $fromFormat->toFloat( $var );
		}

		return $this->format( $var );
	}

	public function toInt( mixed $var ): ?int
	{
		$context = $this->defaultContext;
		$context['precision'] = 0;
		return (int) $this->_format( $var, $context );
	}

	public function toFloat( mixed $var ): ?float
	{
		return $this->format( $var );
	}

	public function toString( mixed $var ): ?string
	{
		return (string) $this->format( $var );
	}

	public function toBool( mixed $var ): ?bool
	{
		return ! empty( $this->format( $var ) );
	}

	public function toArray( mixed $var ): ?array
	{
		return (array) $this->format( $var );
	}

	public function toObject( mixed $var ): ?object
	{
		return (object) $this->format( $var );
	}

	public function supportsFormat( FormatInterface $format ): bool
	{
		return true;
	}
}
