<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Model\TaskModel;

class TaskService
{
	/**
	 * @todo Move to a service?
	 * @return TaskModel[]
	 */
	public static function getCoreTasks(): array
	{
		$tasks = DefaultController::getClassesInNamespace( DefaultController::getRootNamespace() . '\Task' );
		$coreTasks = [];

		foreach ( $tasks as $class ) {
			$task = new $class;
			$coreTasks[ $task->getType() ] = $task;
		}

		return $coreTasks;
	}

	/**
	 * @todo Move to a service?
	 * @return TaskModel[]
	 */
	public static function getModuleTasks( $module = null ): array
	{
		$moduleTasks = [];

		if ( $module ) {
			$modules = [];
			$modules[] = ModuleService::getModule( $module );
		} else {
			$modules = ModuleService::getModules();
		}

		foreach ( $modules as $module ) {
			$tasks = $module->getTasks();
			foreach ( $tasks as $task ) {
				$moduleTasks[ $task->getSlug() ] = $task;
			}
		}

		return $moduleTasks;
	}

	/**
	 * @todo Move to a service?
	 * @return TaskModel[]
	 */
	public static function getTasks(): array
	{
		return array_merge( self::getModuleTasks(), self::getCoreTasks() );
	}

	/**
	 * @todo Move to a service?
	 * @return array
	 */
	public static function getTaskTypes(): array
	{
		return array_keys( self::getTasks() );
	}

	public static function getTask( $name ): object|null
	{
		$tasks = self::getTasks();
		return $tasks[ $name ] ?? null;
	}
}
