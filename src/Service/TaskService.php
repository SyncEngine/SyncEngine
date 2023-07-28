<?php

namespace App\Service;

use App\Component\ExecutionContext;
use App\Controller\DefaultController;
use App\Model\ModuleModel;
use App\Model\TaskModel;

class TaskService
{
	/**
	 * @return TaskModel[]
	 */
	public static function getTasks(): array
	{
		return array_merge( self::getModuleTasks(), self::getCoreTasks() );
	}

	/**
	 * @return TaskModel[]
	 */
	public static function getCoreTasks(): array
	{
		$namespace = DefaultController::getRootNamespace() . '\Task';
		$tasks     = DefaultController::getClassesInNamespace( $namespace );
		$coreTasks = [];

		foreach ( $tasks as $class ) {
			/* @var TaskModel $task */
			$task = new $class;

			$coreTasks[ $task->getClassName() ] = $task;
		}

		return $coreTasks;
	}

	/**
	 * @return TaskModel[]
	 */
	public static function getModuleTasks( $module = null ): array
	{
		$moduleTasks = [];

		if ( $module ) {
			$modules   = [];
			$modules[] = Modules::getModule( $module );
		} else {
			$modules = Modules::getModules();
		}

		foreach ( $modules as $module ) {
			$tasks = $module->getTasks();
			foreach ( $tasks as $task ) {
				$moduleTasks[ $task->getClassName() ] = $task;
			}
		}

		return $moduleTasks;
	}

	public static function getTask( $name ): TaskModel|null
	{
		if ( ! str_contains( $name, ':' ) ) {
			return self::getCoreTask( $name );
		}

		$name = explode( ':', $name );

		return self::getModuleTask( $name[0], $name[1] );
	}

	public static function getCoreTask( $name ): TaskModel|null
	{
		$class = DefaultController::getRootNamespace() . '\\Task\\' . $name;
		if ( class_exists( $class ) ) {
			$task = new $class();
			if ( $task instanceof TaskModel ) {
				return $task;
			}
		}

		return null;
	}

	public static function getModuleTask( $module, $task ): TaskModel|null
	{
		$module = Modules::getModule( $module );
		if ( ModuleModel::isModule( $module ) ) {
			return $module->getTask( $task );
		}

		return null;
	}

	/**
	 * @return array
	 */
	public static function getTaskTypes(): array
	{
		return array_keys( self::getTasks() );
	}

	/**
	 * @return array[]
	 */
	public static function getTasksNormalized(): array
	{
		$tasks = [];
		foreach ( self::getTasks() as $key => $task ) {
			$tasks[ $key ] = $task->normalize();
		}

		return $tasks;
	}
}
