<?php

namespace App\Controller;

use App\Model\Trait\Task;

class TasksController extends AdminController
{
	/**
	 * @todo Move to a service?
	 * @return Task[]
	 */
	public function getCoreTasks(): array {

		$tasks = $this->getClassesInNamespace( DefaultController::getRootNamespace() . '\Task' );
		$coreTasks = [];

		foreach ( $tasks as $class ) {
			$task = new $class;
			$coreTasks[ $task->getType() ] = $task;
		}

		return $coreTasks;
	}

	/**
	 * @todo Move to a service?
	 * @return Task[]
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
	 * @return Task[]
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

	public function getTask( $name ): Task|null
	{
		$tasks = $this->getTasks();
		return $tasks[ $name ] ?? null;
	}
}
