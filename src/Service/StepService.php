<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Step;

class StepService
{
	public static function execute( Step $step, $data, $context = array() ): array
	{
		return self::executeConfig( $step->getConfig(), $data, $context );
	}

	public static function executeConfig( array $config, $data, $context ): array
	{
		$tasks = $config['tasks'] ?? [];
		if ( $tasks ) {
			foreach ( $tasks as $task ) {
				if ( ! empty( $task['module'] ) ) {
					$data = self::executeModule( $task["module"], $task, $data, $context );
				} else {
					$data = self::executeTask( $task, $data, $context );
				}
			}
		}
		return $data;
	}

	public static function executeTask(array $config, $data): array
	{
		$task = $config['type'] ?? '';
		if ($task) {
			$task = ( new TaskService() )->getTask( $task );
			if ( $task ) {
				$data = $task->execute($config, $data);
			}
		}
		return $data;
	}

	public static function executeModule(string $moduleName, array $config, array $data): array
	{
		return ModuleService::getModule($moduleName)->executeConfig( $config, $data );
	}

	public static function getStep( int $id ): Step|null
	{
		return DefaultController::getEntityManager()->getRepository( Step::class )->findOneBy( [ 'id' => $id ] );
	}

	public static function getSteps(): array
	{
		return DefaultController::getEntityManager()->getRepository( Step::class )->findAll();
	}
}
