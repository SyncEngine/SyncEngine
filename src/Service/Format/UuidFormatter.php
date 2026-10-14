<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;
use Symfony\Component\Uid\Uuid;

class UuidFormatter extends StringFormatter implements FormatInterface
{
	const FALLBACK  = 'fallback';
	const VERSION   = 'version';
	const NAMESPACE = 'namespace';
	const NAME 	    = 'name';

	private array $defaultContext = [
		self::FALLBACK  => false,
		self::VERSION => null,
		self::NAMESPACE => null,
		self::NAME => null,
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
		parent::__construct( $defaultContext );
	}

	public function toUuid( $var, array $context = [] ): Uuid
	{
		if ( $var instanceof Uuid ) {
			return $var;
		}

		$var     = (string) $var;
		$context = $context ?: $this->defaultContext;

		if ( ! Uuid::isValid( $var ) && ! empty( $context[ self::FALLBACK ] ) ) {
			$version   = $context[ self::VERSION ] ?? null;
			$namespace = $context[ self::NAMESPACE ] ?? Uuid::NAMESPACE_DNS;
			$name      = $context[ self::NAME ] ?? $var;
			return match ( (int) $version ) {
				1 => Uuid::v1(),
				3 => Uuid::v3( Uuid::fromString( $namespace ), $name ),
				4 => Uuid::v4(),
				5 => Uuid::v5( Uuid::fromString( $namespace ), $name ),
				6 => Uuid::v6(),
				7 => Uuid::v7(),
				8 => Uuid::v8( $var ),
				default => throw new \InvalidArgumentException( "Invalid UUID string: {$var}" ),
			};
		}

		return Uuid::fromString( (string) $var );
	}

	/**
	 * @param  Uuid  $var
	 * @param  array  $context
	 *
	 * @return string
	 */
	public function _format( mixed $var, array $context = [] ): string
	{
		if ( ! $var instanceof Uuid ) {
			// Do not pass format since at this point it is unknown.
			$var = $this->toUuid( $var );
		}

		return $var->toRfc4122();
	}

	/**
	 * @param  mixed                 $var
	 * @param  FormatInterface|null  $fromFormat
	 *
	 * @return mixed
	 */
	public function convert( mixed $var, ?FormatInterface $fromFormat = null ): mixed
	{
		if ( $fromFormat instanceof StringFormatter ) {
			$var = $fromFormat->toUuid( $var );
		}

		return $this->format( $var );
	}

	public function toInt( mixed $var ): ?int
	{
		return $var ? 1 : 0;
	}

	public function toFloat( mixed $var ): ?float
	{
		return (float) $this->toInt( $var );
	}

	public function toArray( mixed $var ): ?array
	{
		if ( is_string( $var ) || $var instanceof Uuid ) {
			$uuid = $this->toUuid( $var );
			return [
				'uuid'   => $uuid->toRfc4122(),
				'base58' => $uuid->toBase58(),
				'base32' => $uuid->toBase32(),
				'binary' => $uuid->toBinary(),
				'hex'    => $uuid->toHex(),
			];
		}

		return null;
	}

	public function toObject( mixed $var ): ?object
	{
		return $this->toUuid( $var );
	}

	public function supportsFormat( FormatInterface $format ): bool
	{
		return $format instanceof StringFormatter;
	}
}
