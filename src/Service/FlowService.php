<?php

namespace App\Service;

use App\Component\ExecutionContext;
use App\Controller\DefaultController;
use App\Entity\Flow;
use App\Model\FlowModel;

class FlowService
{
	public static function execute( FlowModel $flow, ExecutionContext $context, $data ): array
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

	public static function getFlow( Flow|int $flow ): FlowModel|null
	{
		if ( ! $flow instanceof Flow ) {
			$flow = DefaultController::getEntityManager()->getRepository( Flow::class )->findOneBy( [ 'id' => $flow ] );
		}
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
