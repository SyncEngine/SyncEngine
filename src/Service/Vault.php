<?php

namespace SyncEngine\Service;

use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use SyncEngine\Service\Interface\SettingsInterface;

/**
 * @implements \ArrayAccess<string, string>
 */
class Vault extends AbstractVault implements SettingsInterface, \ArrayAccess
{
	private string $env = 'SYNCENGINE_VAULT';

	private array $secrets;

	public function __construct(
		#[Autowire(service: 'secrets.vault')]
		private readonly AbstractVault $vault,
	) {}

	public function fetch(): ?array
	{
		if ( ! isset( $this->secrets ) ) {
			$secrets = [];
			$encoded = $this->vault->reveal( $this->env );
			if ( $encoded ) {

				$decoded = base64_decode( $encoded );
				if ( false === $decoded ) {
					throw new \Exception( 'Cannot decode vault.' );
				}

				$decoded = json_decode( $decoded, true );
				if ( null === $decoded ) {
					throw new \Exception( 'Cannot decode vault.' );
				}

				$secrets = (array) $decoded;
			}
			$this->secrets = $secrets;
		}

		return $this->secrets;
	}

	public function get( string $key = '' ): mixed
	{
		$this->fetch();

		if ( $key ) {
			return $this->secrets[ $key ] ?? null;
		}

		return $this->secrets;
	}

	public function set( string $key, mixed $value ): static
	{
		$this->secrets[ $key ] = $value;

		return $this;
	}

	public function unset( string $key ): static
	{
		unset( $this->secrets[ $key ] );

		return $this;
	}

	public function update( string $key, mixed $value ): bool
	{
		if ( null === $value ) {
			$this->unset( $key );
		} else {
			$this->set( $key, $value );
		}
		return $this->persist();
	}

	public function persist(): bool
	{
		ksort( $this->secrets );

		$secrets = json_encode( $this->secrets );
		$secrets = base64_encode( $secrets );

		return $this->write( $secrets );
	}

	final public function write( string $value ): bool
	{
		$this->vault->seal( $this->env, $value );

		return true;
	}

	public function generateKeys( bool $override = false ): bool
	{
		return $this->vault->generateKeys( $override );
	}

	public function seal( string $name, string $value ): void
	{
		$this->update( $name, $value );
	}

	public function reveal( string $name ): ?string
	{
		return $this->get( $name );
	}

	public function remove( string $name ): bool
	{
		return $this->update( $name, null );
	}

	/**
	 * @return string[]
	 */
	public function list( bool $reveal = false ): array
	{
		$secrets = $this->get();

		if ( $reveal ) {
			return $secrets;
		}

		return array_keys( $secrets );
	}

	public function offsetExists( mixed $offset ): bool
	{
		return null !== $this->get( $offset );
	}

	public function offsetGet( mixed $offset ): mixed
	{
		return $this->get( $offset );
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->set( $offset, $value );
	}

	public function offsetUnset( mixed $offset ): void
	{
		$this->remove( $offset );
	}
}
