<?php

namespace SyncEngine\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Vault
{
	private string $env = 'SYNCENGINE_VAULT';
	private array $secrets;

	public function __construct(
		#[Autowire( '%env(SYNCENGINE_VAULT)%' )]
		private readonly string $vault,
		private readonly System $system,
	) {}

	public function fetch(): array
	{
		if ( ! isset( $this->secrets ) ) {
			$this->secrets = (array) json_decode( base64_decode( $this->vault ), true ) ?? [];
		}

		return $this->secrets;
	}

	public function get( string $name = '' ): mixed
	{
		$this->fetch();

		if ( $name ) {
			return $this->secrets[ $name ] ?? null;
		}

		return $this->secrets;
	}

	public function set( $name, $value ): void
	{
		$this->secrets[ $name ] = $value;
	}

	final public function persist(): void
	{
		$secrets = json_encode( $this->secrets );
		$secrets = base64_encode( $secrets );

		// Set vault value.
		$process = $this->system->getCommandProcess( [ 'secrets:set', $this->env, '-' ] );
		$commandSet = $process->getCommandLine();

		$process = $this->system->getProcessRaw( "echo $secrets | " . $commandSet );

		$this->system->runProcess( $process );
	}
}
