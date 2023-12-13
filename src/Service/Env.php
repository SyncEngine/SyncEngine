<?php

namespace SyncEngine\Service;

use Symfony\Component\Dotenv\Dotenv;

class Env
{
	private string $type;
	private string $file;
	private array $vars = [];

	public function __construct(
		private readonly string $projectDir,
	) {}

	public function setEnvFile( string $type = '' ): void
	{
		$this->type = $type;
		$this->file = $this->projectDir . '/' . ( ( $type ) ? '.env.' . $type : '.env' );
	}

	public function getEnvFileType(): string
	{
		return $this->type;
	}

	public function fetch(): array
	{
		if ( ! $this->vars ) {
			$this->vars = $this->read();
		}

		return $this->vars;
	}

	public function get( string $name = '' ): mixed
	{
		$this->fetch();

		if ( $name ) {
			return $this->vars[ $name ] ?? null;
		}

		return $this->vars;
	}

	public function set( $name, $value ): void
	{
		$this->vars[ $name ] = $value;
	}

	public function persist( bool $replace = false ): void
	{
		if ( ! $replace ) {
			$this->vars = array_merge( $this->read(), $this->vars );
		}

		$this->write( $this->vars );
	}

	private function read(): array
	{
		if ( file_exists( $this->file ) ) {
			$dotenv = new Dotenv();
			$vars = $dotenv->parse( file_get_contents( $this->file ) );
			if ( $vars ) {
				return $vars;
			}
		}

		return [];
	}

	private function write( array $vars ): void
	{
		ksort( $vars );

		foreach ( $vars as $key => $value ) {
			$vars[ $key ] = $key . '=' . ( ( preg_match("/( |:|\/|{|}|\\|#|\(|\)|\*|\?|\|)/i", $value ) ) ? '"' . $value . '"' : $value );
		}

		$vars = implode( PHP_EOL, $vars );

		file_put_contents( $this->file, $vars );
	}
}
