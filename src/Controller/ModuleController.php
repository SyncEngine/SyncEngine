<?php

namespace App\Controller;

use App\Service\ModuleService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModuleController extends AdminController
{
	#[Route('/modules', name: 'modules')]
	public function index(): Response
	{
		$modules = ModuleService::getModules();

		foreach ( $modules as $key => $module ) {
			$modules[ $key ] = [
				'name' => $module->getName(),
			];
		}

		return $this->render('module/index.html.twig', [
			'modules' => $modules,
		]);
	}
}
