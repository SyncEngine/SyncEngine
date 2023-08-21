<?php

namespace App\Service;

class Env
{
	private string $file;
	private array $vars = [];

	public function __construct(
		private readonly string $projectDir,
		public readonly string $type = ''
	) {
		$this->file = $this->projectDir . '/' . ( $type ) ? '.env.' . $type : '.env';
	}

	public function get( $name ): mixed
	{
		if ( ! $this->vars ) {
			$this->vars = $this->read();
		}

		return $this->vars[ $name ] ?? null;
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
			$env = parse_ini_file( $this->file );
			if ( $env ) {
				return $env;
			}
		}

		return [];
	}

	private function write( array $vars = [], ): void
	{
		ksort( $vars );

		foreach ( $vars as $key => $value ) {
			$vars[ $key ] = $key . '=' . ( is_numeric( $value ) ) ? $value : '"' . $value . '"';
		}

		$vars = implode( PHP_EOL, $vars );

		file_put_contents( $this->file, $vars );
	}
}
