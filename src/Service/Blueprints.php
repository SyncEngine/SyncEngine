<?php

namespace SyncEngine\Service;

use Symfony\Component\DependencyInjection\ServiceLocator;
use SyncEngine\Model\BlueprintModel;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class Blueprints
{
	public function __construct(
		private readonly string $dir,
		private readonly ServiceLocator $container,
		private readonly Modules $modulesService,
		protected readonly TranslatorInterface $translator,
	) {}

	public function get( $name ): ?BlueprintModel
	{
		if ( is_file( $this->dir . DIRECTORY_SEPARATOR . $name ) ) {
			$file = new File( $name );
			$ext = $file->getExtension();

			if ( 'php' !== $ext ) {
				$blueprint = ( new Formatter() )->decode( $ext, $file->getContent() );

				if ( $blueprint ) {
					$blueprint = new BlueprintModel( $blueprint );
					$blueprint->setFile( $file );
					return $blueprint;
				}
			}
			return null;
		}

		try {
			$task = $this->container->get( $name ) ?? null;

			if ( $task instanceof BlueprintModel ) {
				if ( str_contains( $name, ':' ) ) {
					$parts  = explode( ':', $name );
					$module = $this->modulesService->getModule( $parts[0] );

					$task->setModule( $module );
				}
				return $task;
			}
		} catch ( \Throwable $throwable ) {
			// Nope.
		}

		// @todo Error or log.
		return null;
	}

	public function getAll(): ?array
	{
		static $blueprints = [];
		if ( $blueprints ) {
			return $blueprints;
		}

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			$blueprint = $this->get( $tag );
			if ( $blueprint ) {
				$blueprints[ $blueprint::getClassLocator() ] = $blueprint;
			}
		}

		$finder = new Finder();
		$finder->in( $this->dir );

		foreach ( $finder->files() as $file ) {
			$ext       = $file->getExtension();
			$content   = $file->getContents();

			if ( 'php' === $ext ) {
				continue; // @todo Allow objects? Autowire?
			}

			$blueprint = ( new Formatter() )->decode( $ext, $content );

			if ( $blueprint ) {
				$blueprint = new BlueprintModel( $blueprint );
				$blueprint->setFile( new File( $file->getRealPath() ) );
				$blueprints[ $file->getFilename() ] = $blueprint;
			}
		}

		return $blueprints;
	}

	/**
	 * @return array
	 */
	public function getTypes(): array
	{
		return array_keys( $this->getAll() );
	}

	/**
	 * @return array[]
	 */
	public function getNormalized(): array
	{
		$blueprints = [];
		foreach ( $this->getAll() as $key => $blueprint ) {
			$blueprints[ $key ] = $blueprint->normalize();
		}

		return $blueprints;
	}
}
