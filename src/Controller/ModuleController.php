<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModuleController extends AbstractController
{

	public function executeModuleStep( string $moduleName, $config, $datafields )
	{
		$moduleClass = "modules\\".$moduleName."\\".$moduleName;
		$mod = new $moduleClass;

		return $mod->executeStep( $config, $datafields );
	}
}
