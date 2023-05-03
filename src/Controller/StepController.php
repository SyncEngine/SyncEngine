<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Step;

class StepController extends AbstractController
{
	public function executeStep(Step $step, array $data )
	{
		$config = $step->getConfig();

		if ( ! empty( $config['module'] ) ) {
			$data = $this->executeModule( $config['module'], $config, $data );
		} else {
			$data = $this->executeTask( $config, $data );
		}

		return $data;
	}

	public function executeTask( array $config, array $data ): array
	{
		$task = $config['task'] ?? '';
		if ( $task ) {
			// Do core task.
		}
		return $data;
	}

	public function executeModule( string $moduleName, array $config, array $data ): array
	{
		return ModulesController::getModule( $moduleName )->executeConfig( $config, $data );
	}
}
