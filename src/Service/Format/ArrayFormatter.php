<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;

class ArrayFormatter implements FormatInterface
{
	const LIST = 'list';

	private array $defaultContext = [
		self::LIST => true,
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
	}

	public function _format( mixed $var, array $context = [] ): array
	{
		$context = $context ?: $this->defaultContext;

		if ( ! is_array( $var ) ) {
			$var = (array) $var;
		}

		if ( ! empty( $context[ self::LIST ] ) ) {
			if ( ! array_is_list( $var ) ) {
				$var = array_values( (array) $var );
			}
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
			return $fromFormat->toArray( $var );
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
		return ( array ) $var;
	}

	public function toInt( mixed $var ): ?int
	{
		return count( $this->format( $var ) );
	}

	public function toFloat( mixed $var ): ?float
	{
		return count( $this->format( $var ) );
	}

	public function toString( mixed $var ): ?string
	{
		return null;
	}

	public function toBool( mixed $var ): ?bool
	{
		return ! empty( $this->format( $var ) );
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
