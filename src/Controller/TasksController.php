<?php

namespace App\Controller;

use Symfony\Component\Finder\Finder;
use App\Controller\ModulesController;

class TasksController extends AdminController
{
	/**
	 * @return TaskController[]
	 */
	public function getTaskTypes(): array {

		$tasks = $this->getClassesInNamespace( 'App\Controller\Task' );
		$taskTypes = [];

		foreach ( $tasks as $class ) {
			$task = new $class;
			$taskTypes[ $task->getType() ] = $task;
		}

		$modulesCont = new ModulesController();

		$modules = $modulesCont->getModules();
		foreach ( $modules as $module ) {
			$tasks = $module->getTasks();
			foreach ( $tasks as $task ) {
				$taskTypes[ $task->getType() ] = $task;
			}
		}

		return $taskTypes;
	}
}
