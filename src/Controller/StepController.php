<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Step;

class StepController extends AbstractController
{
	public function executeStep(Step $step, array $data )
	{
		$config = $step->getConfig();

		$data = $this->executeConfig( $config, $data );

		return $data;
	}

	public function executeConfig( array $config, array $data ): array
	{
		$tasks = $config['tasks'] ?? [];
		if ( $tasks ) {
			foreach ( $tasks as $task ) {
				if ( ! empty( $task['module'] ) ) {
					$data = $this->executeModule( $task['module'], $task, $data );
				} else {
					$data = $this->executeTask( $task, $data );
				}
			}
		}
		return $data;
	}

	public function executeTask( array $config, array $data ): array
	{
		$task = $config['task'] ?? '';
		if ( $task ) {
			// @todo Log error.
			// Do core task.
		}
		return $data;
	}

	public function executeModule( string $moduleName, array $config, array $data ): array
	{
		return ModulesController::getModule( $moduleName )->executeConfig( $config, $data );
	}
}
