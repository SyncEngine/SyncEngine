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
		if ( empty( $conditionals ) || $step->check_conditionals( $conditionals, $data, $context ) ) {

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

	public static function getStep( Step|int $step ): StepModel|null
	{
		if ( ! $step instanceof Step ) {
			$step = DefaultController::getEntityManager()->getRepository( Step::class )->findOneBy( [ 'id' => $step ] );
		}
		if ( $step ) {
			return new StepModel( $step );
		}
		return null;
	}

	public static function getSteps(): array
	{
		$steps = DefaultController::getEntityManager()->getRepository( Step::class )->findAll();
		$models = [];
		foreach ( $steps as $step ) {
			$models[ $step->getId() ] = new StepModel( $step );
		}
		return $models;
	}
}
