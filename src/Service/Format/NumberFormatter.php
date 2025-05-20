<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

class NumberFormatter extends FloatFormatter implements FormatInterface
{
	public const DECIMALS            = 'decimals';
	public const DECIMAL_SEPARATOR   = 'decimal_separator';
	public const THOUSANDS_SEPARATOR = 'thousands_separator';
	public const ROUND               = 'round';
	public const TRIM                = 'trim';

	private array $defaultContext = [
		self::DECIMALS            => 0,
		self::DECIMAL_SEPARATOR   => '.',
		self::THOUSANDS_SEPARATOR => ',',
		//self::ROUND               => 'up',
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
		parent::__construct( $defaultContext );
	}

	/**
	 * @param  mixed  $var
	 * @param  array  $context
	 *
	 * @return string
	 */
	protected function _format( $var, array $context = [] )
	{
		$context = $context ?: $this->defaultContext;

		if ( ! empty( $context[ self::ROUND ] ) ) {
			$floatContext = [];

			$floatContext[ FloatFormatter::MODE ]      = $context[ self::ROUND ];
			$floatContext[ FloatFormatter::PRECISION ] = $context[ self::DECIMALS ];

			$var = FloatFormatter::_format( $var, $floatContext );
		} else {
			$var = $this->sanitize( $var );
		}

		$trim = ! empty( $context[ self::TRIM ] );
		unset( $context[ self::ROUND ] );
		unset( $context[ self::TRIM ] );

		$formatted = number_format( $var, ...$context );

		if ( $trim ) {
			// Remove (0) at the and and if needed the decimal sign.
			$formatted = rtrim( $formatted, '0' . ( $context[ self::DECIMAL_SEPARATOR ] ?? '' ) );
		}

		return $formatted;
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
		$var = $this->sanitize( $var );

		$t   = $this->defaultContext[ static::THOUSANDS_SEPARATOR ];
		$var = str_replace( $t, '', $var );

		$d   = $this->defaultContext[ static::DECIMAL_SEPARATOR ];
		$var = explode( $d, $var )[0];

		return (int) $var;
	}

	public function toFloat( mixed $var ): ?float
	{
		$var = $this->sanitize( $var );

		$t   = $this->defaultContext[ static::THOUSANDS_SEPARATOR ];
		$d   = $this->defaultContext[ static::DECIMAL_SEPARATOR ];
		$var = str_replace( [ $t, $d ], [ '', '.' ], $var );

		return (float) $var;
	}

	public function toString( mixed $var ): ?string
	{
		return $this->format( $var );
	}

	public function toBool( mixed $var ): ?bool
	{
		return ! empty( $this->toFloat( $var ) );
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
