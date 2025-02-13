<?php

namespace SyncEngine\Service;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Service\Interface\SettingsInterface;

class Env implements SettingsInterface
{
	private string $type;
	private string $file;
	private array $vars = [];
	private array $unset = [];

	public function __construct(
		private readonly string $projectDir,
	) {}

	public function setEnvFile( string $type = '' ): void
	{
		$this->type = $type;
		$this->file = $this->projectDir . '/' . ( ( $type ) ? '.env.' . $type : '.env' );
		$this->vars = [];
	}

	public function getEnvFileType(): string
	{
		return $this->type;
	}

	public function fetch(): ?array
	{
		if ( ! $this->vars ) {
			$this->vars = $this->read();
		}

		return $this->vars;
	}

	public function get( string $key = '' ): mixed
	{
		$this->fetch();

		if ( $key ) {
			return $this->vars[ $key ] ?? null;
		}

		return $this->vars;
	}

	public function set( string $key, mixed $value ): static
	{
		$this->vars[ $key ] = $value;
		unset( $this->unset[ $key ] );

		return $this;
	}

	public function unset( string $key ): static
	{
		$this->unset[ $key ] = null;
		unset( $this->vars[ $key ] );

		return $this;
	}

	public function update( string $key, mixed $value ): bool
	{
		$this->set( $key, $value );
		return $this->persist();
	}

	public function persist( bool $replace = false ): bool
	{
		if ( ! $replace ) {
			$this->vars = array_merge( $this->read(), $this->vars );
		}

		foreach ( $this->unset as $key => $null ) {
			unset( $this->vars[ $key ] );
		}
		$this->unset = [];

		return $this->write( $this->vars );
	}

	private function read(): array
	{
		$fs = new Filesystem();
		if ( ! $fs->exists( $this->file ) ) {
			$fs->touch( $this->file );
		}

		if ( $fs->exists( $this->file ) ) {
			$dotenv = new Dotenv();
			$vars = $dotenv->parse( file_get_contents( $this->file ) );
			if ( $vars ) {
				return $vars;
			}
		}

		return [];
	}

	private function write( array $vars ): bool
	{
		ksort( $vars );

		foreach ( $vars as $key => $value ) {
			$vars[ $key ] = $key . '=' . ( ( preg_match("/( |:|\/|{|}|\\|#|\(|\)|\*|\?|\|)/i", $value ) ) ? '"' . $value . '"' : $value );
		}

		$vars = implode( PHP_EOL, $vars );

		$success = file_put_contents( $this->file, $vars );

		return false !== $success;
	}
}
