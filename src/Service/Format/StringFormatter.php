<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

class StringFormatter implements FormatInterface
{
	public function fromFormat( mixed $var, ?FormatInterface $format = null ): ?string
	{
		if ( $format instanceof FormatInterface ) {
			return $format->toString( $var );
		}

		return (string) $var;
	}

	public function toFormat( mixed $var ): ?string
	{
		return $this->fromFormat( $var );
	}

	public function toInt( mixed $var ): ?int
	{
		return (int) $this->fromFormat( $var );
	}

	public function toFloat( mixed $var ): ?float
	{
		return (float) $this->fromFormat( $var );
	}

	public function toString( mixed $var ): ?string
	{
		return $this->fromFormat( $var );
	}

	public function toBool( mixed $var ): ?bool
	{
		return ! empty( $this->fromFormat( $var ) );
	}

	public function toArray( mixed $var ): ?array
	{
		return (array) $this->toFormat( $var );
	}

	public function toObject( mixed $var ): ?object
	{
		return (object) $this->toFormat( $var );
	}

	public function supportsFormat( FormatInterface $format ): bool
	{
		return true;
	}
}
