<?php

namespace App\Service;

use App\Component\AutomationContext;
use App\Controller\DefaultController;
use App\Model\ModuleModel;
use App\Model\TaskModel;

class TaskService
{
	public static function execute( array $config, $data, AutomationContext $context ): array
	{
		$task = $config['type'] ?? '';
		if ( $task ) {
			$task = TaskService::getTask( $task );
			if ( $task ) {
				$context->startTask( $task );

				// @todo Create Resource class/trait to parse objects as resource.
				$resource = [
					'context' => $context->getContext(),
				];
				$resource['context']['cache'] = $context->getCache();

				$data = $task->execute( $task->parseTagArray( $resource, $config ), $context, $data );
				$context->endTask();
			}
		}
		return $data;
	}

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

	public static function getCoreTask( $task ): TaskModel|null
	{
		$tasks = self::getCoreTasks();
		if ( isset( $tasks[ $task ] ) ) {
			return $tasks[ $task ];
		}
		return null;
	}

	public static function getModuleTask( $module, $task ): TaskModel|null
	{
		$module = ModuleService::getModule( $module );
		if ( ModuleModel::isModule( $module ) ) {
			return $module->getTask( $task );
		}
		return null;
	}

	public static function getTask( $name ): TaskModel|null
	{
		$tasks = self::getTasks();
		return $tasks[ $name ] ?? null;
	}
}
