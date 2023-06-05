<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Step;

class FlowService
{
	public function execute($flow, $data )
	{
		$stepController = new StepService();
		foreach ( $flow->getSteps() as $stepID )
		{
			$step = DefaultController::getEntityManager()->getRepository( Step::class )->findOneBy(['id'=>$stepID]);
			$data = $stepController->execute( $step, $data );
		}
		return $data;
	}
}
