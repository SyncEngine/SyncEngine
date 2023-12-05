<?php

namespace App\Service;

use App\Model\ModuleModel;
use App\Model\TaskModel;
use Symfony\Component\DependencyInjection\ServiceLocator;

class Tasks
{
	public function __construct( private ServiceLocator $container ) {}

	public function getTask( $name ): TaskModel|null
	{
		try {
			$task = $this->container->get( $name ) ?? null;

			if ( $task instanceof TaskModel ) {
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
	public function getModuleTasks( $module = null ): array
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
