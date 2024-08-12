<?php

namespace SyncEngine\Service\Format;

use DateInterval;
use SyncEngine\Service\Interface\FormatInterface;

class DurationFormatter extends DateTimeFormatter implements FormatInterface
{
	const FORMAT   = 'format';
	const NOW = 'now';

	private array $defaultContext = [
		self::FORMAT => '%h:%i:%s',
		self::NOW    => 'now', // Provide a date to use for now.
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
		// Default DateTime format.
		$defaultContext[ DateTimeFormatter::FORMAT ] = $this->defaultContext[ 'Y-m-d H:i:s.u' ];
		parent::__construct( $defaultContext );
	}

	public function toInterval( mixed $var, array $context = [] ): \DateInterval
	{
		if ( $var instanceof \DateInterval ) {
			return $var;
		}

		$now = $context[ self::NOW ] ?? $this->defaultContext[ self::NOW ];
		if ( 'now' !== $now ) {
			$now = $this->toDateTime( $now );
			$var = $this->toDateTime( $var, $context );

			return $now->diff( $var );
		}

		return DateInterval::createFromDateString( $var );
	}

	/**
	 * @param  \DateTimeInterface  $var
	 * @param  array  $context
	 *
	 * @return string
	 */
	public function _format( mixed $var, array $context = [] ): string
	{
		if ( ! $var instanceof \DateInterval ) {
			$var = $this->toInterval( $var, $context );
		}

		return $var->format( $this->defaultContext[ self::FORMAT ] );
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
			if ( $fromFormat instanceof DurationFormatter ) {
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
