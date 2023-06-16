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
				$data = TaskService::execute( $taskConfig, $data, $context );
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
