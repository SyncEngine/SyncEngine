<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

class DateTimeFormatter extends StringFormatter implements FormatInterface
{
	const FORMAT = 'format';

	private array $defaultContext = [
		self::FORMAT => '',
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
		parent::__construct( $defaultContext );
	}

	public function toDateTime( $var, array $context = [] ): \DateTime
	{
		$context = $context ?: $this->defaultContext;
		$format  = $context[ self::FORMAT ] ?? null;

		if ( $format ) {
			return \DateTime::createFromFormat( $format, $var );
		}

		try {
			if ( is_numeric( $var ) ) {
				return ( new \DateTime() )->setTimestamp( $var );
			}

			return new \DateTime( $var );
		} catch ( \Exception $e ) {
			return new \DateTime();
		}
	}

	public function _format( mixed $var, array $context = [] ): string
	{
		if ( ! $var instanceof \DateTimeInterface ) {
			$var = $this->toDateTime( $var, [ self::FORMAT => '' ] );
		}

		$format  = $context[ self::FORMAT ] ?? $this->defaultContext[ self::FORMAT ];

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
