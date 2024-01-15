<?php

namespace SyncEngine\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use SyncEngine\Service\Interface\SettingsInterface;

class Vault implements SettingsInterface
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
			$this->secrets = (array) json_decode( base64_decode( $this->vault ), true ) ?? [];
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

		return $this->system->runProcess( $process );
	}
}
