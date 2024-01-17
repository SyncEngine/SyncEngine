<?php

namespace SyncEngine\Service;

use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use SyncEngine\Service\Interface\SettingsInterface;

class Vault extends AbstractVault implements SettingsInterface
{
	private string $env = 'SYNCENGINE_VAULT';
	private array $secrets;

	public function __construct(
		#[Autowire( '%env(SYNCENGINE_VAULT)%' )]
		private readonly ?string $vault,
		private readonly System $system,
	) {}

	public function fetch(): ?array
	{
		if ( ! isset( $this->secrets ) ) {
			$secrets = [];
			if ( $this->vault ) {

				$vault = base64_decode( $this->vault );
				if ( false === $vault ) {
					throw new \Exception( 'Cannot decode vault.' );
				}

				$vault = json_decode( $vault, true );
				if ( null === $vault ) {
					throw new \Exception( 'Cannot decode vault.' );
				}

				$secrets = (array) $vault;
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
		// Set vault value.
		$process = $this->system->getCommandProcess( [ 'secrets:set', $this->env, '-' ] );
		$commandSet = $process->getCommandLine();

		$process = $this->system->getProcessRaw( "echo $value | " . $commandSet );

		$success = $this->system->runProcess( $process );

		if ( $success ) {
			return $success;
		}

		// Debug.

		$success = $this->system->runProcess( $process, false );
		$this->lastMessage = $success['status'];

		return $success['success'];
	}

	public function generateKeys( bool $override = false ): bool
	{
		$command = [ '--no-interaction', 'secrets:generate-keys' ];

		if ( $override ) {
			$command[] = '--rotate';
		}

		return $this->system->runCommand( $command );
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

	public function list( bool $reveal = false ): array
	{
		$secrets = $this->get();

		if ( $reveal ) {
			return $secrets;
		}

		return array_keys( $secrets );
	}
}
