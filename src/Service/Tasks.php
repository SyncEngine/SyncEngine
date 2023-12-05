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

	public function getTask( $name ): TaskModel|null
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
	public function getTasks(): array
	{
		static $tasks = [];
		if ( $tasks ) {
			return $tasks;
		}

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			$task = $this->getTask( $tag );
			if ( $task ) {
				$tasks[ $task::getClassName() ] = $task;
			}
		}

		return $tasks;
	}

	/**
	 * @return TaskModel[]
	 */
	public function getModuleTasks( ModuleModel|string $module ): array
	{
		$tasks = [];

		$moduleName = $module instanceof ModuleModel ? $module::getClassName() : $module;

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			if ( str_starts_with( $tag . ':', $moduleName ) ) {
				$task = $this->getTask( $tag );
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
	public function getTaskTypes(): array
	{
		return array_keys( $this->getTasks() );
	}

	/**
	 * @return array[]
	 */
	public function getTasksNormalized(): array
	{
		$tasks = [];
		foreach ( $this->getTasks() as $key => $task ) {
			$tasks[ $key ] = $task->normalize();
		}

		return $tasks;
	}
}
