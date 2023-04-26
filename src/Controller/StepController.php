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
			$data = $this->executeModuleStep( $config['module'], $config, $data );
		}

		return $data;
	}

	public function executeModuleStep( string $moduleName, $config, $data )
	{
		$moduleClass = "modules\\".$moduleName."\\".$moduleName;
		$mod = new $moduleClass;

		return $mod->executeStep( $config, $data );
	}
}
