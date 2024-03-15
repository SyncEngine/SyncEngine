<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

class NumberFormatter implements FormatInterface
{
	public const DECIMALS = 'decimals';
	public const DECIMAL_SEPARATOR = 'decimal_separator';
	public const THOUSANDS_SEPARATOR = 'thousands_separator';

	private array $defaultContext = [
		self::DECIMALS            => 0,
		self::DECIMAL_SEPARATOR   => '.',
		self::THOUSANDS_SEPARATOR => ',',
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
	}

	private function _format( $var, array $context = null ): string
	{
		$context = $context ?: $this->defaultContext;

		if ( ! is_numeric( $var ) ) {
			$var = $this->sanitize( $var );
		}

		return number_format( $var, ...$context );
	}

	private function sanitize( $var )
	{
		return filter_var( $var, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND );
	}

	public function format( mixed $var ): ?string
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
		$var = $this->sanitize( $var );

		$t = $this->defaultContext[ static::THOUSANDS_SEPARATOR ];
		$var = str_replace( $t, '', $var );

		$d = $this->defaultContext[ static::DECIMAL_SEPARATOR ];
		$var = explode( $d, $var )[0];

		return (int) $var;
	}

	public function toFloat( mixed $var ): ?float
	{
		$var = $this->sanitize( $var );

		$t = $this->defaultContext[ static::THOUSANDS_SEPARATOR ];
		$d = $this->defaultContext[ static::DECIMAL_SEPARATOR ];
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
