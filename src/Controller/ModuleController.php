<?php

namespace App\Controller;

use App\Service\ModuleService;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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

	#[Route('/modules/upload', name: 'module_upload')]
	public function uploadModule( Request $request ): Response
	{
		$form = $this->createFormBuilder([])
			->add( 'module', FileType::class )
			->getForm();

		$form->handleRequest( $request );

		if ($form->isSubmitted() && $form->isValid()) {
			$file = $form['module']->getData();
			$this->install( $file );
        }

		return $this->render( 'module/upload.html.twig', [
			'form' => $form,
		] );
	}

	private function install( $file ) {

	}
}
