<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

// @todo Check https://github.com/commerceguys/intl
class CurrencyFormatter extends NumberFormatter implements FormatInterface
{
	public const DECIMALS            = 'decimals';
	public const DECIMAL_SEPARATOR   = 'decimal_separator';
	public const THOUSANDS_SEPARATOR = 'thousands_separator';
	public const ROUND               = 'round';
	public const DECIMAL_DASH        = 'decimal_dash';
	public const CURRENCY_SIGN       = 'currency_sign';
	public const CURRENCY_SPACE      = 'currency_space';
	public const CURRENCY_AFTER      = 'currency_after';

	private array $defaultContext = [
		//self::ROUND               => 'up',
		self::DECIMALS            => 0,
		self::DECIMAL_SEPARATOR   => '.',
		self::THOUSANDS_SEPARATOR => ',',
		self::DECIMAL_DASH        => false,
		self::CURRENCY_SIGN       => '',
		self::CURRENCY_SPACE      => false,
		self::CURRENCY_AFTER      => false,
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

		unset( $context[ self::ROUND ] );

		$numberContext = [
			self::DECIMALS            => $context[ self::DECIMALS ],
			self::DECIMAL_SEPARATOR   => $context[ self::DECIMAL_SEPARATOR ],
			self::THOUSANDS_SEPARATOR => $context[ self::THOUSANDS_SEPARATOR ],
		];

		$postfix = '';

		if ( ! empty( $context[ self::DECIMAL_DASH ] ) ) {
			$hasDecimals = ( (int) $var < $var );
			if ( ! $hasDecimals ) {
				$numberContext[ self::DECIMALS ] = 0;
			}

			$postfix = $context[ self::DECIMAL_SEPARATOR ] . '-';
		}

		$price = number_format( $var, ...$numberContext ) . $postfix;

		if ( ! empty( $context[ self::CURRENCY_SIGN ] ) ) {
			$currency = $context[ self::CURRENCY_SIGN ];
			$space = ! empty( $context[ self::CURRENCY_SPACE ] ) ? ' ' : '';

			if ( ! empty( $context[ self::CURRENCY_AFTER ] ) ) {
				$price .= $space . $currency;
			} else {
				$price = $currency . $space . $price;
			}
		}

		return $price;
	}

	public function supportsFormat( FormatInterface $format ): bool
	{
		return true;
	}
}
