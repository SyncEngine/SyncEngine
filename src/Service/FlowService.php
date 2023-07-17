<?php

namespace App\Service;

use App\Component\ExecutionContext;
use App\Controller\DefaultController;
use App\Entity\Flow;
use App\Model\FlowModel;
use App\Model\StepModel;

class FlowService
{
	public static function execute( FlowModel $flow, ExecutionContext $context, $data ): array
	{
		$context->startFlow( $flow );

		foreach ( $flow->getSteps() as $stepID ) {
			$step = StepModel::get( $stepID );
			$data = StepService::execute( $step, $context, $data );
		}

		$context->endFlow();

		return $data;
	}
}
