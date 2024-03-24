<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

class DateTimeFormatter extends StringFormatter implements FormatInterface
{
	const FORMAT   = 'format';
	const TIMEZONE = 'timezone';

	private array $defaultContext = [
		/**
		 * @link https://www.php.net/manual/en/datetime.format.php
		 */
		self::FORMAT   => null,
		/**
		 * @link https://www.php.net/manual/en/timezones.php
		 */
		self::TIMEZONE => null,
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
		parent::__construct( $defaultContext );
	}

	public function getTimezone( $context = [] ): ?\DateTimeZone
	{
		$timezone = $context[ self::TIMEZONE ] ?? $this->defaultContext[ self::TIMEZONE ];

		if ( $timezone && ! $timezone instanceof \DateTimeZone ) {
			try {
				return new \DateTimeZone( $timezone );
			} catch ( \Exception $e ) {
				return null;
			}
		}

		return null;
	}

	public function toDateTime( $var, array $context = [] ): \DateTimeInterface
	{
		$context  = $context ?: $this->defaultContext;
		$format   = $context[ self::FORMAT ] ?? null;
		$timezone = $this->getTimezone( $context );

		if ( $format ) {
			return \DateTimeImmutable::createFromFormat( $format, $var, $timezone );
		}

		try {
			if ( is_numeric( $var ) ) {
				$datetime = ( new \DateTimeImmutable() )->setTimestamp( $var );
				return ( $timezone ) ? $datetime->setTimezone( $timezone ) : $datetime;
			}

			return new \DateTimeImmutable( $var, $timezone );
		} catch ( \Exception $e ) {
			if ( $timezone ) {
				return ( new \DateTimeImmutable() )->setTimezone( $timezone );
			}
			return ( new \DateTimeImmutable() );
		}
	}

	/**
	 * @param  \DateTimeInterface  $var
	 * @param  array  $context
	 *
	 * @return string
	 */
	public function _format( mixed $var, array $context = [] ): string
	{
		if ( ! $var instanceof \DateTimeInterface ) {
			$var = $this->toDateTime( $var, [ self::FORMAT => '' ] );
		}

		$timezone = $this->getTimezone( $context );
		if ( $timezone ) {
			$var = $var->setTimezone( $timezone );
		}

		$format = $context[ self::FORMAT ] ?? $this->defaultContext[ self::FORMAT ];

		return $var->format( $format );
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
			if ( $fromFormat instanceof DateTimeFormatter ) {
				$var = $fromFormat->toDateTime( $var );
			} elseif ( $fromFormat instanceof StringFormatter ) {
				$var = $fromFormat->toString( $var );
			}
		}

		return $this->format( $var );
	}

	public function supportsFormat( FormatInterface $format ): bool
	{
		return true;
	}
}
