<?php

namespace App\Controller;

use App\Service\Modules;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;

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
			'modules'     => $modules,
			'breadcrumbs' => [
				[
					'link'    => $this->generateUrl( 'modules' ),
					'title'   => 'Modules',
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
			$this->_install( $file );

			return $this->redirectToRoute( 'module_upload' );
		}

		return $this->render( 'admin/module/upload.html.twig', [
			'form'        => $form,
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

		if(is_array($response)){
			return $this->render( 'admin/index.html.twig', $response );
		}

		return ( $response instanceof Response ) ? $response : $this->redirectToRoute( 'modules' );
	}

	#[Route( '/module/uninstall/{name}', name: 'module_uninstall' )]
	public function uninstall( string $name, Request $request ): Response
	{
		$module = Modules::getModule( $name );
		if ( $module->uninstall() ) {
			$this->addFlash( 'success', $name . ' succesfully uninstalled' );
		} else {
			$this->addFlash( 'warning', 'Uninstall unsuccessful' );
			return $this->redirectToRoute( 'modules' );
		}

		$filesystem = new Filesystem();
		$filesystem->remove( $this->getParameter( 'modules_directory' ) . DIRECTORY_SEPARATOR . $name );

		return $this->redirectToRoute( 'modules' );
	}

	private function _install( $file )
	{
		$moduleName = pathinfo( $file->getClientOriginalName(), PATHINFO_FILENAME );

		$this->_extract( $file );

		$module = Modules::getModule( $moduleName );

		if ( $module->install() ) {
			$this->addFlash( 'success', $moduleName . ' succesfully installed' );
		} else {
			$this->addFlash( 'warning', 'Cant run install of ' . $moduleName );
		}
	}

	private function _extract( $file )
	{
		$dir  = $this->getParameter( 'modules_directory' );
		$name = $file->getClientOriginalName();

		try {
			$file->move( $dir, $name );
		} catch ( FileException $e ) {
			$this->addFlash( 'warning', 'Cant place file!' );

			return $this->redirectToRoute( 'module_upload' );
		}

		$filesystem = new Filesystem();
		$zipfile    = $dir . DIRECTORY_SEPARATOR . $name;

		$zip = new \ZipArchive;
		if ( true === $zip->open( $zipfile ) ) {
			$zip->extractTo( $dir );
			$zip->close();
		} else {
			$this->addFlash( 'warning', 'Cant unzip file!' );
		}

		$filesystem->remove( $zipfile );
	}
}
