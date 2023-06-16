<?php

namespace App\Service;

use App\Component\AutomationContext;
use App\Controller\DefaultController;
use App\Entity\Step;

class StepService
{
	public static function execute( Step $step, $data, AutomationContext $context ): array
	{
		$context->setCurrentStep( $step );
		return self::executeConfig( $step->getConfig(), $data, $context );
	}

	public static function executeConfig( array $config, $data, AutomationContext $context ): array
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

	public static function executeTask( array $config, $data, AutomationContext $context ): array
	{
		$task = $config['type'] ?? '';
		if ($task) {
			$task = ( new TaskService() )->getTask( $task );
			if ( $task ) {
				$data = $task->execute( $config, $data, $context );
			}
		}
		return $data;
	}

	public static function executeModule(string $moduleName, array $config, array $data, AutomationContext $context ): array
	{
		return ModuleService::getModule($moduleName)->executeConfig( $config, $data, $context );
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
