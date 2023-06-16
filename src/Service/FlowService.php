<?php

namespace App\Service;

use App\Component\AutomationContext;
use App\Controller\DefaultController;
use App\Entity\Flow;

class FlowService
{
	public static function execute( Flow $flow, $data, AutomationContext $context ): array
	{
		$context->descend();

		$context->setCurrentFlow( $flow );

		$stepService  = new StepService();
		foreach ( $flow->getSteps() as $stepID )
		{
			$step = $stepService->getStep( $stepID );
			$data = $stepService->execute( $step, $data, $context );
		}

		$context->ascend();
		return $data;
	}

	public static function getFlow( int $id ): Flow|null
	{
		return DefaultController::getEntityManager()->getRepository( Flow::class )->findOneBy( [ 'id' => $id ] );
	}

	public static function getFlows(): array
	{
		return DefaultController::getEntityManager()->getRepository( Flow::class )->findAll();
	}
}
