<?php

namespace App\Service;

use App\Component\AutomationContext;
use App\Controller\DefaultController;
use App\Entity\Step;

class StepService
{
	public static function execute( Step $step, $data, AutomationContext $context ): array
	{
		$context->startStep( $step );
		$data = self::executeConfig( $step->getConfig(), $data, $context );
		$context->endStep();
		return $data;
	}

	public static function executeConfig( array $config, $data, AutomationContext $context ): array
	{
		$tasks = $config['tasks'] ?? [];
		if ( $tasks ) {
			foreach ( $tasks as $taskConfig ) {
				$data = self::executeTask( $taskConfig, $data, $context );
			}
		}
		return $data;
	}

	public static function executeTask( array $config, $data, AutomationContext $context ): array
	{
		$task = $config['type'] ?? '';
		if ($task) {
			if ( ! empty( $taskConfig['module'] ) ) {
				$task = TaskService::getModuleTasks( $task );
			} else {
				$task = TaskService::getTask( $task );
			}
			if ( $task ) {
				$data = $task->execute( $config, $data, $context );
			}
		}
		return $data;
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
