<?php

namespace SyncEngine\Model\Enum\Trait;

trait EnumStringTrait
{
	public static function create( string|self $value ): ?static
	{
		if ( $value instanceof static ) {
			return $value;
		}

		$value = strtolower( $value );

		return static::tryFrom( $value );
	}
	public static function is( mixed $value, self $trait ): bool
	{
		return static::create( $value ) === $trait;
	}

	public static function isValid( mixed $value ): bool
	{
		return ! empty( static::create( $value ) );
	}
}
