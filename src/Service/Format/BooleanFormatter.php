<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

class BooleanFormatter implements FormatInterface
{
	/**
	 * @param  mixed                 $var
	 * @param  FormatInterface|null  $fromFormat
	 *
	 * @return bool
	 */
	public function formatFrom( mixed $var, ?FormatInterface $fromFormat = null )
	{
		if ( $fromFormat instanceof FormatInterface ) {
			return $fromFormat->toBool( $var );
		}

		return ! empty( $var );
	}

	/**
	 * @param  mixed  $var
	 *
	 * @return bool
	 */
	public function format( mixed $var )
	{
		return $this->formatFrom( $var );
	}

	public function toInt( mixed $var ): ?int
	{
		return (int) $this->formatFrom( $var );
	}

	public function toFloat( mixed $var ): ?float
	{
		return (float) $this->formatFrom( $var );
	}

	public function toString( mixed $var ): ?string
	{
		return (string) $this->formatFrom( $var );
	}

	public function toBool( mixed $var ): ?bool
	{
		return $this->formatFrom( $var );
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
