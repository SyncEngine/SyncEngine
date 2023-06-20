<?php

namespace App\Service;

use App\Component\AutomationContext;
use App\Controller\DefaultController;
use App\Entity\Step;

class StepService
{
	public static function execute( Step $step, AutomationContext $context, $data ): array
	{
		$context->startStep( $step );
		$data = self::executeConfig( $step->getConfig(), $context, $data );
		$context->endStep();
		return $data;
	}

	public static function executeConfig( array $config, AutomationContext $context, $data ): array
	{
		$tasks = $config['tasks'] ?? [];
		if ( $tasks ) {
			foreach ( $tasks as $taskConfig ) {
				$data = TaskService::execute( $taskConfig, $context, $data );
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
