<?php

namespace SyncEngine\Service\Provider;

use Symfony\Component\DependencyInjection\ServiceLocator;
use SyncEngine\Model\ModuleModel;
use SyncEngine\Model\TaskModel;

class Tasks implements ProviderInterface
{
	public function __construct(
		private readonly ServiceLocator $container,
		private readonly Modules $modulesService,
	) {}

	public function get( $name ): ?TaskModel
	{
		try {
			$task = $this->container->get( $name ) ?? null;

			if ( $task instanceof TaskModel ) {
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
	 * @return TaskModel[]
	 */
	public function getAll(): array
	{
		static $tasks = [];
		if ( $tasks ) {
			return $tasks;
		}

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			$task = $this->get( $tag );
			if ( $task ) {
				$tasks[ $task->getClassLocator() ] = $task;
			}
		}

		return $tasks;
	}

	/**
	 * @return TaskModel[]
	 */
	public function getAllFromModule( ModuleModel|string $module ): array
	{
		$tasks = [];

		$moduleName = $module instanceof ModuleModel ? $module->getClassLocator() : $module;

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			if ( str_starts_with( $tag . ':', $moduleName ) ) {
				$task = $this->get( $tag );
				if ( $task ) {
					$tasks[ $task->getClassLocator() ] = $task;
				}
			}
		}

		return $tasks;
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
		$tasks = [];
		foreach ( $this->getAll() as $key => $task ) {
			$tasks[ $key ] = $task->normalize();
		}

		return $tasks;
	}
}
