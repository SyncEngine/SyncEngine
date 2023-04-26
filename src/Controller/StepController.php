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
			$data = $this->executeModuleTask( $config['module'], $config, $data );
		}

		return $data;
	}

	public function executeModuleTask( string $moduleName, $config, $data )
	{
		return ModulesController::getModule( $moduleName )->executeTask( $config, $data );
	}
}
