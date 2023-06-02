<?php

namespace App\Controller;

use App\Controller\ModulesController;
use Symfony\Component\Finder\Finder;

class TasksController extends AdminController
{
	/**
	 * @todo Move to a service?
	 * @return TaskController[]
	 */
	public function getCoreTasks(): array {

		$tasks = $this->getClassesInNamespace( 'App\Controller\Task' );
		$coreTasks = [];

		foreach ( $tasks as $class ) {
			$task = new $class;
			$coreTasks[ $task->getType() ] = $task;
		}

		return $coreTasks;
	}

	/**
	 * @todo Move to a service?
	 * @return TaskController[]
	 */
	public function getModuleTasks( $module = null ): array {

		$moduleTasks = [];

		if ( $module ) {
			$modules = [];
			$modules[] = ModulesController::getModule( $module );
		} else {
			$modules = ModulesController::getModules();
		}

		foreach ( $modules as $module ) {
			$tasks = $module->getTasks();
			foreach ( $tasks as $task ) {
				$moduleTasks[ $task->getType() ] = $task;
			}
		}

		return $moduleTasks;
	}

	/**
	 * @todo Move to a service?
	 * @return TaskController[]
	 */
	public function getTasks(): array
	{
		return array_merge( $this->getModuleTasks(), $this->getCoreTasks() );
	}

	/**
	 * @todo Move to a service?
	 * @return array
	 */
	public function getTaskTypes(): array
	{
		return array_keys( $this->getTasks() );
	}
}
