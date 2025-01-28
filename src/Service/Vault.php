<?php

namespace SyncEngine\Service;

use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Service\Interface\SettingsInterface;

class Vault extends AbstractVault implements SettingsInterface, \ArrayAccess
{
	private string $env = 'SYNCENGINE_VAULT';

	private array $secrets;

	public function __construct(
		#[Autowire( '%env(SYNCENGINE_VAULT)%' )]
		private readonly ?string $vault,
		private readonly System $system,
		private readonly string $projectDir,
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
		$command = 'secrets:set';

		$tmp = $this->projectDir . '/var/tmp';
		if ( ! is_dir( $tmp ) ) {
			( new Filesystem() )->mkdir( $tmp );
		}

		$file = tempnam( $tmp, 'tmp' );

		try {
			$stream = fopen( $file, 'w' );

			fwrite( $stream, $value );

			$args = [
				'name' => $this->env,
				'file' => $file,
			];

			// Set secrets in PHP cache.
			$success = $this->system->runCommand( $command, $args );

			// Reset cache.
			fclose( $stream );

		} catch ( ExceptionInterface $exception ) {
			$this->lastMessage = $exception->getMessage();
			return false;
		}

		unlink( $file );

		if ( $success ) {
			return $success;
		}

		// Debug.

		$success = $this->system->runCommand( $command, $args, false );
		$this->lastMessage = $success['status'];

		return $success['success'];
	}

	public function generateKeys( bool $override = false ): bool
	{
		$command = 'secrets:generate-keys';

		if ( $override ) {
			return $this->system->runCommand( $command, options: [ 'rotate' => true ] );
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
