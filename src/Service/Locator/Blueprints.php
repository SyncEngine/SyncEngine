<?php

namespace SyncEngine\Service\Locator;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyncEngine\Exception\InvalidException;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\BlueprintModel;
use SyncEngine\Service\DataFormatter;

/**
 * @extends AbstractServiceModelLocator<BlueprintModel>
 */
class Blueprints extends AbstractServiceModelLocator
{
	public function __construct(
		private readonly string $dir,
		protected readonly TranslatorInterface $translator,
		ServiceLocator $container,
		Modules $modulesService,
		LoggerInterface $logger,
	) {
		parent::__construct( $container, $modulesService, $logger );
	}

	public function validate( ServiceModel $service ): bool
	{
		return $service instanceof BlueprintModel;
	}

	public function getModelClass(): string
	{
		return BlueprintModel::class;
	}

	public function get( $name, bool $throwOnError = false ): ?BlueprintModel
	{
		$file_location = $this->dir . DIRECTORY_SEPARATOR . $name;

		if ( is_file( $file_location ) ) {
			$file = new File( $file_location );
			$ext = $file->getExtension();

			if ( 'php' !== $ext ) {
				$blueprint = ( new DataFormatter() )->decode( $ext, $file->getContent() );

				if ( $blueprint ) {
					$blueprint = new BlueprintModel( $blueprint );
					$blueprint->setFile( $file );
					return $blueprint;
				}
			}

			$this->logger->warning( 'File "' . $file_location . '" is not a valid ' . $this->getModelClass() );
			return null;
		}

		try {
			$blueprint = $this->container->get( $name ) ?? null;

			if ( $blueprint instanceof BlueprintModel ) {
				if ( str_contains( $name, ':' ) ) {
					$parts  = explode( ':', $name );
					$module = $this->modulesService->get( $parts[0] );

					$blueprint->setModule( $module );
				}

				return $blueprint;

			} else {
				$this->logger->warning( 'Service "' . $name . '" is not a valid ' . $this->getModelClass() );
				if ( $throwOnError ) {
					throw new InvalidException( 'Service "' . $name . '" is not a valid ' . $this->getModelClass() );
				}
				return null;
			}
		} catch ( \Throwable $e ) { /** @var NotFoundExceptionInterface $e */
			$this->logger->warning( 'Service "' . $name . '" is not a valid ' . $this->getModelClass() );
			if ( $throwOnError ) {
				throw $e;
			}
			return null;
		}
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
