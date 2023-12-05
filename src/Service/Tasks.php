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
		if ( class_exists( $name ) && is_callable( [ $name, 'getClassName' ] ) && $name::getClassName() ) {
			$name = $name::getClassName();
		}

		try {
			$task = $this->container->get( $name ) ?? null;

			if ( $task instanceof ModuleModel ) {
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

		foreach ( $this->container->getProvidedServices() as $tag ) {
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

		foreach ( $this->container->getProvidedServices() as $tag ) {

			if ( class_exists( $tag ) && is_callable( [ $tag, 'getClassName' ] ) && $tag::getClassName() ) {
				$name = $tag::getClassName();
			}

			if ( str_starts_with( $name . ':', $moduleName ) ) {
				$task = $this->getTask( $name );
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
