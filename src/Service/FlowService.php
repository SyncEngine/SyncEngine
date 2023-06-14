<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Flow;

class FlowService
{
	public function execute( Flow $flow, $data, $context = [] ): array
	{
		if ( ! $context ) {
			$context = $data;
		}

		$stepService  = new StepService();
		foreach ( $flow->getSteps() as $stepID )
		{
			$step = $stepService->getStep( $stepID );
			$data = $stepService->execute( $step, $data, $context );
		}
		return $data;
	}

	public function getFlow( int $id ): Flow|null
	{
		return DefaultController::getEntityManager()->getRepository( Flow::class )->findOneBy( [ 'id' => $id ] );
	}

	public function getFlows(): array
	{
		return DefaultController::getEntityManager()->getRepository( Flow::class )->findAll();
	}
}
