<?php

namespace App\Service;

use App\Component\ExecutionContext;
use App\Controller\DefaultController;
use App\Entity\Step;
use App\Model\StepModel;

class StepService
{
	public static function execute( StepModel $step, ExecutionContext $context, $data ): array
	{
		$context->startStep( $step );

		$config = $step->getConfig();

		$conditionals = $config['conditionals'] ?? [];
		if ( empty( $conditionals ) || $step->validateConditionals( $conditionals, $data, $context ) ) {

			$tasks = $config['tasks'] ?? [];
			if ( $tasks ) {
				foreach ( $tasks as $taskConfig ) {
					$data = TaskService::execute( $taskConfig, $context, $data );
				}
			}
		}

		$context->endStep();

		return $data;
	}
}
