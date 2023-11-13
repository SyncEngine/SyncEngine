<?php

namespace App\Controller;

use App\Service\Modules;
use PhpParser\Node\Expr\AssignOp\Mod;
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
	#[Route( '/json/module/{name}', name: 'json_module' )]
	public function moduleJson( string $name, Request $request, Modules $modules ): Response
	{
		$module = $modules->getModule( $name );

		$response = $module->handleRequest( $request );

		return ( $response instanceof Response ) ? $response : new Response();
	}

	#[Route( '/module/{name}', name: 'module' )]
	public function module( string $name, Request $request, Modules $modules ): Response
	{
		$module = $modules->getModule( $name );

		$response = $module->renderRequest( $request );

		if ( is_array( $response ) ) {
			return $this->render( 'admin/index.html.twig', $response );
		}

		return ( $response instanceof Response ) ? $response : $this->redirectToRoute( 'modules' );
	}

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
					'title'   => $this->trans( 'Modules' ),
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
					'mimeTypesMessage' => $this->trans( 'Please upload a valid ZIP file' ),
				] ),
			],
		] )->add( 'submit', SubmitType::class, [ 'label' => $this->trans( 'Upload' ) ] )->getForm();

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
					'title' => $this->trans( 'Modules' ),
				],
				[
					'link'    => $this->generateUrl( 'module_upload' ),
					'title'   => $this->trans( 'Upload' ),
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/module/uninstall/{name}', name: 'module_uninstall' )]
	public function uninstall( string $name, Request $request,Modules $modules ): Response
	{
		$module = $modules->getModule( $name );
		if ( $module->uninstall() ) {
			$this->addFlash( 'success', $this->trans( '%moduleName% succesfully uninstalled', [ 'moduleName', $name ] ) );
		} else {
			$this->addFlash( 'warning', $this->trans( 'Uninstall unsuccessful' ) );
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
			$this->addFlash( 'success', $this->trans( '%moduleName% successfully installed', [ 'moduleName', $moduleName ] ) );
		} else {
			$this->addFlash( 'warning', $this->trans( 'Cant run install of %moduleName%', [ 'moduleName', $moduleName ] ) );
		}
	}

	private function _extract( $file )
	{
		$dir  = $this->getParameter( 'modules_directory' );
		$name = $file->getClientOriginalName();

		try {
			$file->move( $dir, $name );
		} catch ( FileException $e ) {
			$this->addFlash( 'warning', $this->trans( 'Cant place file!' ) );

			return $this->redirectToRoute( 'module_upload' );
		}

		$filesystem = new Filesystem();
		$zipfile    = $dir . DIRECTORY_SEPARATOR . $name;

		$zip = new \ZipArchive;
		if ( true === $zip->open( $zipfile ) ) {
			$zip->extractTo( $dir );
			$zip->close();
		} else {
			$this->addFlash( 'warning', $this->trans( 'Cant unzip file!' ) );
		}

		$filesystem->remove( $zipfile );
	}
}
