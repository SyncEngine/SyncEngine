<?php

namespace SyncEngine\Service\Provider;

use Symfony\Component\DependencyInjection\ServiceLocator;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Model\ModuleModel;

class Columns implements ProviderInterface
{
	public function __construct(
		private readonly ServiceLocator $container,
		private readonly Modules $modulesService,
	) {}

	public function get( $name ): ?ColumnModel
	{
		try {
			$column = $this->container->get( $name ) ?? null;

			if ( $column instanceof ColumnModel ) {
				if ( str_contains( $name, ':' ) ) {
					$parts  = explode( ':', $name );
					$module = $this->modulesService->get( $parts[0] );

					$column->setModule( $module );
				}
				return $column;
			}
		} catch ( \Throwable $throwable ) {
			// Nope.
		}

		// @todo Error or log.
		return null;
	}

	/**
	 * @return ColumnModel[]
	 */
	public function getAll(): array
	{
		static $columns = [];
		if ( $columns ) {
			return $columns;
		}

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			$column = $this->get( $tag );
			if ( $column ) {
				$columns[ $column->getClassLocator() ] = $column;
			}
		}

		return $columns;
	}

	/**
	 * @return ColumnModel[]
	 */
	public function getAllFromModule( ModuleModel|string $module ): array
	{
		$columns = [];

		$moduleName = $module instanceof ModuleModel ? $module->getClassLocator() : $module;

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			if ( str_starts_with( $tag . ':', $moduleName ) ) {
				$column = $this->get( $tag );
				if ( $column ) {
					$columns[ $column->getClassLocator() ] = $column;
				}
			}
		}

		return $columns;
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
		$columns = [];
		foreach ( $this->getAll() as $key => $column ) {
			$columns[ $key ] = $column->normalize();
		}

		return $columns;
	}
}
