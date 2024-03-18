<?php

namespace SyncEngine\Service\Format;

use Symfony\Component\String\UnicodeString;
use SyncEngine\Service\Interface\FormatInterface;

class StringFormatter implements FormatInterface
{
	const TRIM = 'trim';

	private array $defaultContext = [
		self::TRIM => false,
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
	}

	public function _format( mixed $var, array $context = [] )
	{
		if ( ! is_scalar( $var ) ) {
			return '';
		}

		// @todo Use string component?

		$var = (string) $var;

		if ( ! empty( $context[ self::TRIM ] ) ) {
			$var = trim( $var );
		}

		return $var;
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
			return $fromFormat->toString( $var );
		}

		return $this->format( $var );
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

	public function toInt( mixed $var ): ?int
	{
		return (int) $this->format( $var );
	}

	public function toFloat( mixed $var ): ?float
	{
		return (float) $this->format( $var );
	}

	public function toString( mixed $var ): ?string
	{
		return $this->format( $var );
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
