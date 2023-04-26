<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Step;

class StepController extends AbstractController
{
	public function ExecuteStep(Step $step, array $data )
	{
		$config = $step->getConfig();

		if ( ! empty( $config['module'] ) ) {
			$moduleController = new ModuleController();
			$data = $moduleController->executeModuleStep( $config['module'], $config, $data );
		}

		return $data;
	}
}
