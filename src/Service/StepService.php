<?php

namespace App\Service;

use App\Component\AutomationContext;
use App\Controller\DefaultController;
use App\Entity\Step;
use App\Model\StepModel;

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
