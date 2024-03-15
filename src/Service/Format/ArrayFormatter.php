<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

class ArrayFormatter implements FormatInterface
{
	public function formatFrom( mixed $var, ?FormatInterface $fromFormat = null ): ?array
	{
		if ( $fromFormat instanceof FormatInterface ) {
			return $fromFormat->format( $var );
		}

		return (array) $var;
	}

	public function format( mixed $var ): ?array
	{
		return $this->formatFrom( $var );
	}

	public function toInt( mixed $var ): ?int
	{
		return count( $this->formatFrom( $var ) );
	}

	public function toFloat( mixed $var ): ?float
	{
		return count( $this->formatFrom( $var ) );
	}

	public function toString( mixed $var ): ?string
	{
		return null;
	}

	public function toBool( mixed $var ): ?bool
	{
		return ! empty( $this->formatFrom( $var ) );
	}

	public function toArray( mixed $var ): ?array
	{
		return $this->format( $var );
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
