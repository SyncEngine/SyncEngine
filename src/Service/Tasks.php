<?php

namespace SyncEngine\Service;

use SyncEngine\Model\ModuleModel;
use SyncEngine\Model\TaskModel;
use Symfony\Component\DependencyInjection\ServiceLocator;

class Tasks
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
				$tasks[ $task::getClassName() ] = $task;
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

		$moduleName = $module instanceof ModuleModel ? $module::getClassName() : $module;

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			if ( str_starts_with( $tag . ':', $moduleName ) ) {
				$task = $this->get( $tag );
				if ( $task ) {
					$tasks[ $task::getClassName() ] = $task;
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
