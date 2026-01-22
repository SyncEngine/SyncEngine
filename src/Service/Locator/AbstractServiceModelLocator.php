<?php

namespace SyncEngine\Service\Locator;

use Symfony\Component\DependencyInjection\ServiceLocator;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\ModuleModel;

/**
 * @template T of ServiceModel
 */
abstract class AbstractServiceModelLocator implements LocatorInterface
{
	private $services;

	public function __construct(
		protected readonly ServiceLocator $container,
		protected readonly Modules $modulesService,
	) {}

	abstract public function validate( ServiceModel $service ): bool;

	/**
	 * @return ServiceModel::class
	 */
	abstract public function getModelClass(): string;

	/**
	 * @param string $name
	 *
	 * @return ?ServiceModel
	 * @psalm-return ?ServiceModel<T>
	 */
	public function get( $name ): ?ServiceModel
	{
		try {
			$service = $this->container->get( $name ) ?? null;

			if ( $this->validate( $service ) ) {
				if ( str_contains( $name, ':' ) ) {
					$parts  = explode( ':', $name );
					$module = $this->modulesService->get( $parts[0] );

					$service->setModule( $module );
				}
				// @todo Convert to factories?
				return $service;
			}
		} catch ( \Throwable $throwable ) {
			// Nope.
		}

		// @todo Error or log.
		return null;
	}

	/**
	 * @return ServiceModel[]
	 * @psalm-return ServiceModel<T>[]
	 */
	public function getAll(): array
	{
		if ( isset( $this->services ) ) {
			return $this->services;
		}

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			$service = $this->get( $tag );
			if ( $service ) {
				$this->services[ $service->getClassLocator() ] = $service;
			}
		}

		return $this->services;
	}

	/**
	 * @return ServiceModel[]
	 * @psalm-return ServiceModel<T>[]
	 */
	public function getAllFromModule( ModuleModel|string $module ): array
	{
		$services = [];

		$moduleName = $module instanceof ModuleModel ? $module->getClassLocator() : $module;

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			if ( str_starts_with( $tag . ':', $moduleName ) ) {
				$service = $this->get( $tag );
				if ( $service ) {
					$services[ $service->getClassLocator() ] = $service;
				}
			}
		}

		return $services;
	}

	/**
	 * @return string[]
	 */
	public function getTypes(): array
	{
		return array_keys( $this->getAll() );
	}

	/**
	 * @todo Move to controller?
	 * @return array[]
	 */
	public function getNormalized(): array
	{
		$services = [];
		foreach ( $this->getAll() as $key => $service ) {
			$services[ $key ] = $service->normalize();
		}

		return $services;
	}
}
