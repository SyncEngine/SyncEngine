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
	 * @return int
	 */
	public function format( mixed $var )
	{
		return $this->_format( $var );
	}

	/**
	 * @param  mixed                 $var
	 * @param  FormatInterface|null  $fromFormat
	 *
	 * @return int
	 */
	public function formatFrom( mixed $var, ?FormatInterface $fromFormat = null )
	{
		if ( $fromFormat instanceof FormatInterface ) {
			return $fromFormat->toInt( $var );
		}

		return $this->format( $var );
	}

	public function toInt( mixed $var ): ?int
	{
		return $this->_format( $var );
	}

	public function toFloat( mixed $var ): ?float
	{
		return (float) $this->_format( $var );
	}

	public function toString( mixed $var ): ?string
	{
		return (string) $this->_format( $var );
	}

	public function toBool( mixed $var ): ?bool
	{
		return ! empty( $this->_format( $var ) );
	}

	public function toArray( mixed $var ): ?array
	{
		return (array) $this->_format( $var );
	}

	public function toObject( mixed $var ): ?object
	{
		return (object) $this->_format( $var );
	}

	public function supportsFormat( FormatInterface $format ): bool
	{
		return true;
	}
}
