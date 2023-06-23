<?php

namespace App\Service;

use App\Component\AutomationContext;
use App\Controller\DefaultController;
use App\Entity\Flow;
use App\Model\FlowModel;

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

	public static function getFlow( int $id ): FlowModel|null
	{
		$flow = DefaultController::getEntityManager()->getRepository( Flow::class )->findOneBy( [ 'id' => $id ] );
		if ( $flow ) {
			return new FlowModel( $flow );
		}
		return null;
	}

	public static function getFlows(): array
	{
		$flows = DefaultController::getEntityManager()->getRepository( Flow::class )->findAll();
		$models = [];
		foreach ( $flows as $flow ) {
			$models[ $flow->getId() ] = new FlowModel( $flow );
		}
		return $models;
	}
}
