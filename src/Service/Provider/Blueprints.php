<?php

namespace SyncEngine\Service\Provider;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\BlueprintModel;
use SyncEngine\Service\DataFormatter;

/**
 * @extends AbstractServiceModelProvider<BlueprintModel>
 */
class Blueprints extends AbstractServiceModelProvider
{
	public function __construct(
		private readonly string $dir,
		ServiceLocator $container,
		Modules $modulesService,
		protected readonly TranslatorInterface $translator,
	) {
		parent::__construct( $container, $modulesService );
	}

	public function validate( ServiceModel $service ): bool
	{
		return $service instanceof BlueprintModel;
	}

	public function get( $name ): ?BlueprintModel
	{
		if ( is_file( $this->dir . DIRECTORY_SEPARATOR . $name ) ) {
			$file = new File( $name );
			$ext = $file->getExtension();

			if ( 'php' !== $ext ) {
				$blueprint = ( new DataFormatter() )->decode( $ext, $file->getContent() );

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
					$module = $this->modulesService->get( $parts[0] );

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

	/**
	 * @inheritDoc
	 * @return BlueprintModel[]
	 */
	public function getAll(): array
	{
		static $blueprints = [];
		if ( $blueprints ) {
			return $blueprints;
		}

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			$blueprint = $this->get( $tag );
			if ( $blueprint ) {
				$blueprints[ $blueprint->getClassLocator() ] = $blueprint;
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

			$blueprint = ( new DataFormatter() )->decode( $ext, $content );

			if ( $blueprint ) {
				$blueprint = new BlueprintModel( $blueprint );
				$blueprint->setFile( new File( $file->getRealPath() ) );
				$blueprints[ $file->getFilename() ] = $blueprint;
			}
		}

		return $blueprints;
	}
}
