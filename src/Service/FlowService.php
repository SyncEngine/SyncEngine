<?php

namespace App\Service;

use App\Component\AutomationContext;
use App\Controller\DefaultController;
use App\Entity\Flow;

class FlowService
{
	public static function execute( Flow $flow, AutomationContext $context, $data ): array
	{
		$context->startFlow( $flow );

		foreach ( $flow->getSteps() as $stepID )
		{
			$step = StepService::getStep( $stepID );
			$data = StepService::execute( $step, $context, $data );
		}

		$context->endFlow();
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
