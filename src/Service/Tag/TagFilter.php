<?php

namespace SyncEngine\Service\Tag;

use SyncEngine\Service\DataFormatter;
use SyncEngine\Service\Format\DateTimeFormatter;

trait TagFilter
{
	protected function filterCount( $value ): string
	{
		if ( ! is_countable( $value ) ) {
			return 0;
		}
		return count( $value );
	}

	protected function filterTrim( $value, ...$config ): string
	{
		return trim( $value, reset( $config ) );
	}

	protected function filterFormat( $value, string $format = ',', ...$config ): mixed
	{
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$formatter = new DataFormatter();

		return $formatter->encodeFormat( $format, $value, $config );
	}

	protected function filterString( $value, string $separator = ',', ...$config ): string
	{
		if ( ! is_array( $value ) ) {
			return (string) $value;
		}

		if ( 2 < strlen( $separator ) ) {
			$formatter = new DataFormatter();

			if ( array_key_exists( $separator, $formatter->getFormats() ) ) {
				$context = $config;
				if ( isset( $context[0] ) && is_array( $context[0] ) ) {
					$context = $context[0];
				}
				return $formatter->encodeFormat( $separator, $value, $context );
			}
		}

		if ( ! empty( $config[0] ) ) {
			$enclose = $config[0];

			return $enclose . implode( $enclose . $separator . $enclose, $value ) . $enclose;
		}

		return implode( $separator, $value );
	}

	protected function filterDate( $value, string $format = '', string $timezone = '' ): string
	{
		$formatter = new DateTimeFormatter( [
			DateTimeFormatter::FORMAT    => $format ?? null,
			DateTimeFormatter::TIMEZONE  => $timezone ?? null,
		] );

		return $formatter->format( $value );
	}
}
