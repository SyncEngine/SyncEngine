<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

class BooleanFormatter implements FormatInterface
{
	const FORMAT      = 'format';
	const TRUE_VALUE  = 'true_value';
	const FALSE_VALUE = 'false_value';
	const NULL_VALUE  = 'null_value';

	private array $defaultContext = [
		self::FORMAT     => 'bool',
		self::NULL_VALUE => null,
	];

	public function parseContext( $context )
	{
		switch ( $context[ self::FORMAT ] ?? $this->defaultContext[ self::FORMAT ] ) {
			case 'bool':
			case 'boolean':
				$context[ self::TRUE_VALUE ]  = true;
				$context[ self::FALSE_VALUE ] = false;
			break;
			case 'int':
			case 'integer':
				$context[ self::TRUE_VALUE ]  = 1;
				$context[ self::FALSE_VALUE ] = 0;
			break;
			default:
				if ( ! isset( $context[ self::TRUE_VALUE ] ) ) {
					throw new \ErrorException( self::TRUE_VALUE . ' is required' );
				}
				if ( ! isset( $context[ self::FALSE_VALUE ] ) ) {
					throw new \ErrorException( self::FALSE_VALUE . ' is required' );
				}
			break;
		}

		return $context;
	}

	public function sanitize( mixed $var, array $context = [] ): ?bool
	{
		$context = $this->parseContext( $context ?: $this->defaultContext );

		if ( $var === $context[ self::TRUE_VALUE ] ) {
			return true;
		}
		if ( $var === $context[ self::FALSE_VALUE ] ) {
			return false;
		}

		return filter_var( $var, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
	}

	public function _format( mixed $var, array $context = [] ): null|bool|int|string
	{
		$context = $this->parseContext( $context ?: $this->defaultContext );

		if ( ! is_bool( $var ) ) {
			$var = $this->sanitize( $context );
		}

		if ( null === $var ) {
			return $context[ self::NULL_VALUE ];
		}

		return $var ? $context[ self::TRUE_VALUE ] : $context[ self::FALSE_VALUE ];
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
			$var = $fromFormat->toBool( $var );
		}

		return $this->format( $var );
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

	public function toInt( mixed $var ): ?int
	{
		return (int) $this->format( $var );
	}

	public function toFloat( mixed $var ): ?float
	{
		return (float) $this->format( $var );
	}

	public function toString( mixed $var ): ?string
	{
		return (string) $this->format( $var );
	}

	public function toBool( mixed $var ): ?bool
	{
		return $this->sanitize( $var );
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
