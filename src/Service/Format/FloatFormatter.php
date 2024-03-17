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
		if ( isset( $context[ static::MODE ] ) && ! is_numeric( $context[ static::MODE ] ) ) {
			switch ( $context[ static::MODE ] ) {
				case 'up':
					$context[ static::MODE ] = PHP_ROUND_HALF_UP;
				break;
				case 'down':
					$context[ static::MODE ] = PHP_ROUND_HALF_DOWN;
				break;
				case 'even':
					$context[ static::MODE ] = PHP_ROUND_HALF_EVEN;
				break;
				case 'odd':
					$context[ static::MODE ] = PHP_ROUND_HALF_ODD;
				break;
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

		return round( (float) $var, ...$context );
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

		$var      = explode( '.', $var );
		$decimals = array_pop( $var );
		$var      = implode( '', $var );

		return (float) $var . ( $decimals ? '.' . $decimals : '' );
	}

	/**
	 * @param  mixed  $var
	 *
	 * @return float
	 */
	public function format( mixed $var )
	{
		return $this->_format( $var );
	}

	public function formatFrom( mixed $var, ?FormatInterface $fromFormat = null ): ?string
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
		return $this->_format( $var );
	}

	public function toString( mixed $var ): ?string
	{
		return (string) $this->_format( $var );
	}

	public function toBool( mixed $var ): ?bool
	{
		return ! empty( $this->_format( $var ) );
	}

	public function toArray( mixed $var ): ?array
	{
		return (array) $this->_format( $var );
	}

	public function toObject( mixed $var ): ?object
	{
		return (object) $this->_format( $var );
	}

	public function supportsFormat( FormatInterface $format ): bool
	{
		return true;
	}
}
