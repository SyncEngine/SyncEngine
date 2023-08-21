<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;

class ClassFinder
{
	public function __construct( private readonly string $projectDir ) {}

	public function getRootNamespace(): string
	{
		return 'App';
	}

	public function getRootDir( bool $trail = false ): string
	{
		return $this->projectDir . ( ( $trail ) ? DIRECTORY_SEPARATOR : '' );
	}

	/**
	 * @param  string  $namespace
	 *
	 * @return array
	 */
	public function getClassesInNamespace( string $namespace ): array
	{
		$classes = [];
		$finder  = new Finder();
		$dir     = trim( str_replace( [ '\\', "\\", "/" ], DIRECTORY_SEPARATOR, $namespace ), "\/\\" );

		if ( str_starts_with( $dir, $this->getRootNamespace() ) ) {
			$dir = 'src' . substr( $dir, 3 );
		}

		$path = $this->getRootDir( true ) . $dir;

		if ( ! is_dir( $path ) ) {
			return [];
		}

		$finder->files()->in( $path )->name( '*.php' );

		foreach ( $finder as $file ) {
			$file_name  = $file->getFilenameWithoutExtension();
			$class_name = rtrim( $namespace, '\\' ) . '\\' . $file_name;

			if ( class_exists( $class_name ) ) {
				try {
					$classes[] = $class_name;
				} catch ( \Throwable $e ) {
					// @todo Notice?
					continue;
				}
			}
		}

		return $classes;
	}

	/**
	 * @param  string  $dir
	 *
	 * @return array
	 */
	public function getClassesInDir( string $dir ): array
	{
		$classes   = [];
		$finder    = new Finder();
		$namespace = str_replace( [ '\\', "\\", "/", DIRECTORY_SEPARATOR ], "\\", $dir );
		$path      = $this->getRootDir( true ) . trim( $dir, "\/\\" );

		if ( str_starts_with( $dir, 'src' ) ) {
			$namespace = $this->getRootNamespace() . substr( $namespace, 3 );
		}

		if ( ! is_dir( $path ) ) {
			return [];
		}

		$finder->files()->in( $path )->depth( '1' )->name( '*.php' );

		foreach ( $finder as $file ) {
			$file_name  = $file->getFilenameWithoutExtension();
			$class_name = rtrim( $namespace, '\\' ) . "\\" . $file_name . "\\" . $file_name;

			if ( class_exists( $class_name ) ) {
				try {
					$classes[] = $class_name;
				} catch ( \Throwable $e ) {
					// @todo Notice?
					continue;
				}
			}
		}

		return $classes;
	}
}
