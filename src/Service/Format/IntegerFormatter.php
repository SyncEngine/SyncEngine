<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

class IntegerFormatter extends FloatFormatter implements FormatInterface
{
	/**
	 * @param  mixed  $var
	 * @param  array  $context
	 *
	 * @return int
	 */
	protected function _format( $var, array $context = [] )
	{
		return (int) parent::_format( $var, $context );
	}

	/**
	 * @param  mixed  $var
	 *
	 * @return mixed
	 */
	public function format( mixed $var ): mixed
	{
		return $this->_format( $var );
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
			return $fromFormat->toInt( $var );
		}

		return $this->format( $var );
	}

	public function toInt( mixed $var ): ?int
	{
		return $this->format( $var );
	}

	public function toFloat( mixed $var ): ?float
	{
		return (float) $this->format( $var );
	}

	public function toString( mixed $var ): ?string
	{
		return (string) $this->format( $var );
	}

	public function toBool( mixed $var ): ?bool
	{
		return ! empty( $this->format( $var ) );
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
