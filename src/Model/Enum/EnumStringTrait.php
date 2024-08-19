<?php

namespace SyncEngine\Model\Enum;

/**
 * @method static static from( $value )
 * @method static static tryFrom( $value )
 */
trait EnumStringTrait
{
	public static function create( int|string|object $value ): ?static
	{
		if ( $value instanceof static ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( $value );
		}

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
