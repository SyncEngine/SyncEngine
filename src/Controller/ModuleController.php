<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use modules\editPrice\editPrice;



class ModuleController extends AbstractController
{

	public function ExecuteModule(string $moduleName,$config, $datafields)
	{
		//@todo variable module autoload
		$mod = new editPrice;
		return $mod->editPrice($config, $datafields);
	}
}
