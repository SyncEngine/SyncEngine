<?php

namespace App\Controller;

use App\Service\Modules;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Filesystem\Filesystem;

class ModuleController extends AdminController
{
	#[Route( '/modules', name: 'modules' )]
	public function index(): Response
	{
		$modules = Modules::getModules();

		foreach ( $modules as $key => $module ) {
			$modules[ $key ] = [
				'name'        => $module->getName(),
				'description' => $module->getDescription(),
				'version'     => $module->getVersion(),
				'author'      => $module->getAuthor(),
			];
		}

		return $this->render( 'admin/module/index.html.twig', [
			'modules' => $modules,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'modules' ),
					'title' => 'Modules',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/modules/upload', name: 'module_upload' )]
	public function uploadModule( Request $request ): Response
	{
		$form = $this->createFormBuilder( [] )->add( 'module', FileType::class, [
				'constraints' => [
					new File( [
						'mimeTypes'        => [
							'application/zip',
							'application/octet-stream',
						],
						'mimeTypesMessage' => 'Please upload a valid ZIP file',
					] ),
				],
			] )->add( 'submit', SubmitType::class, [ 'label' => 'Upload' ] )->getForm();

		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$file = $form['module']->getData();
			$this->install( $file );

			return $this->redirectToRoute( 'module_upload' );
		}

		return $this->render( 'admin/module/upload.html.twig', [
			'form' => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'modules' ),
					'title' => 'Modules',
				],
				[
					'link'    => $this->generateUrl( 'module_upload' ),
					'title'   => 'Upload',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/module/{name}', name: 'module' )]
	public function module( string $name, Request $request ): Response
	{
		$module = Modules::getModule( $name );

		$response = $module->renderRequest( $request );

		return ( $response instanceof Response ) ? $response : $this->redirectToRoute( 'modules' );
	}

	private function install( $file )
	{
		try {
			$file->move( $this->getParameter( 'modules_directory' ), $file->getClientOriginalName() );
		} catch ( FileException $e ) {
			$this->addFlash( 'warning', 'Cant place file!' );

			return $this->redirectToRoute( 'module_upload' );
		}

		$zip = new \ZipArchive;
		if ( $zip->open( $this->getParameter( 'modules_directory' ) . "/" . $file->getClientOriginalName() )
		     === true ) {
			$zip->extractTo( $this->getParameter( 'modules_directory' ) );
			$zip->close();
			$this->addFlash( 'success', 'Module succesfully installed' );
		} else {
			$this->addFlash( 'warning', 'Cant unzip file!' );
		}

		$filesystem = new Filesystem();
		$zipfile    = $this->getParameter( "modules_directory" ) . '/' . $file->getClientOriginalName();
		$filesystem->remove( $zipfile );
	}
}
