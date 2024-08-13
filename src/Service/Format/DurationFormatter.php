<?php

namespace SyncEngine\Service\Format;

use DateInterval;
use SyncEngine\Service\Interface\FormatInterface;

class DurationFormatter extends DateTimeFormatter implements FormatInterface
{
	const FORMAT   = 'format';
	const NOW = 'now';

	private array $numericFormats = [
		'years',
		'months',
		'weeks',
		'days',
		'hours',
		'minutes',
		'seconds',
	];

	private array $defaultContext = [
		self::FORMAT => '%h:%i:%s',
		self::NOW    => 'now', // Provide a date to use for now.
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
		// Default DateTime format.
		$defaultContext[ DateTimeFormatter::FORMAT ] = null;
		parent::__construct( $defaultContext );
	}

	public function isTimestamp( $var ): bool
	{
		return (string) (int) $var === (string) $var;
	}

	public function parseRelativeTime( $var, $context = [] )
	{
		$format = $context[ self::FORMAT ] ?? $this->defaultContext[ self::FORMAT ];

		if ( is_numeric( $var ) && in_array( $format, $this->numericFormats, true ) ) {
			$var = $var . ' ' . $format;
		}

		if ( is_string( $var ) && str_contains( $var, ':' ) ) {
			$dateParts = date_parse( $var );
			if (
				empty( $dateParts['year'] ) &&
				empty( $dateParts['month'] ) &&
				empty( $dateParts['day'] ) &&
				empty( $dateParts['relative'] )
			) {
				/**
				 * Date only contains time entries that would otherwise be converted to today at that time instead of a duration.
				 * Convert it to an actual relative date string to properly create the DateTime object.
				 */
				$var = '';
				if ( ! empty( $dateParts['hour'] ) ) {
					$var .= $dateParts['hour'] . ' hours ';
				}
				if ( ! empty( $dateParts['minute'] ) ) {
					$var .= $dateParts['minute'] . ' minutes ';
				}
				if ( ! empty( $dateParts['second'] ) ) {
					$var .= $dateParts['second'] . ' seconds ';
				}
				$var = trim( $var );
			}
		}

		return $var;
	}

	public function toDateTime( $var, array $context = [] ): \DateTimeInterface
	{
		if ( $var instanceof \DateTimeInterface ) {
			return $var;
		}

		$var = $this->parseRelativeTime( $var, $context );

		unset( $context['format'] );

		return parent::toDateTime( $var, $context );
	}

	public function toInterval( mixed $var, array $context = [] ): \DateInterval
	{
		if ( $var instanceof \DateInterval ) {
			return $var;
		}

		$now = $context[ self::NOW ] ?? $this->defaultContext[ self::NOW ];
		if ( 'now' !== $now || $this->isTimestamp( $var ) ) {
			// Remove format to prevent incorrect parsing of numeric/timestamp values.
			$now = $this->toDateTime( $now, [ self::FORMAT => '' ] );
			$var = $this->toDateTime( $var, $context );

			return $now->diff( $var );
		}

		return DateInterval::createFromDateString( $this->parseRelativeTime( $var, $context ) );
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
			// Remove format to prevent incorrect parsing of numeric/timestamp values.
			$var = $this->toInterval( $var, [ ...$context, self::FORMAT => ''  ] );
		}

		$format = $context[ self::FORMAT ] ?? $this->defaultContext[ self::FORMAT ];

		if ( in_array( $format, $this->numericFormats, true ) ) {
			$totalSeconds = ( new \DateTime() )->setTimestamp( 0 )->add( $var )->getTimestamp();

			return (string) match ( $format ) {
				'years' => $totalSeconds / 31556926,
				'months' => $totalSeconds / 2629743.83, // Approx..
				'weeks' => $totalSeconds / 604800,
				'days' => $totalSeconds / 86400,
				'hours' => $totalSeconds / 3600,
				'minutes' => $totalSeconds / 60,
				'seconds' => $totalSeconds,
			};
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
				$var = $fromFormat->toInterval( $var );
			} elseif ( $fromFormat instanceof DateTimeFormatter ) {
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
