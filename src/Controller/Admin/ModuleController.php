<?php

namespace SyncEngine\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\File;
use SyncEngine\Service\Provider\Modules;

class ModuleController extends AdminController
{
	#[Route( '/json/module/{vendor}/{module}', name: 'json_module' )]
	public function moduleJson( string $vendor, string $module, Request $request, Modules $modules ): Response
	{
		$module = $modules->get( $module, $vendor );

		$response = $module->handleRequest( $request );

		return ( $response instanceof Response ) ? $response : new Response();
	}

	#[Route( '/module/{vendor}/{module}', name: 'module' )]
	public function module( string $vendor, string $module, Request $request, Modules $modules ): Response
	{
		$module = $modules->get( $module, $vendor );

		$response = $module->renderRequest( $request );

		if ( is_array( $response ) ) {
			return $this->render( 'admin/index.html.twig', $response );
		}

		return ( $response instanceof Response ) ? $response : $this->redirectToRoute( 'syncengine_modules' );
	}

	#[Route( '/modules', name: 'modules', options: [
		'menu'         => "leftMain",
		'menuTitle'    => "Dashboard",
		"menuIcon"     => "bi bi-plugin",
		'menuPosition' => 6,
	] )]
	public function index( Modules $modulesService ): Response
	{
		$modules = $modulesService->getAll();

		foreach ( $modules as $key => $module ) {
			$modules[ $key ] = $module->normalize();
		}

		return $this->render(
			'admin/module/index.html.twig',
			[
				'modules'     => $modules,
				'breadcrumbs' => [
					[
						'link'    => $this->generateUrl( 'syncengine_modules' ),
						'title'   => $this->trans( 'Modules' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/modules/upload', name: 'module_upload' )]
	public function uploadModule( Request $request, Modules $modulesService ): Response
	{
		$form = $this->createFormBuilder( [] )->add(
			'module',
			FileType::class,
			[
				'constraints' => [
					new File(
						[
							'mimeTypes'        => [
								'application/zip',
								'application/octet-stream',
							],
							'mimeTypesMessage' => $this->trans( 'Please upload a valid ZIP file' ),
						]
					),
				],
			]
		)->add( 'submit', SubmitType::class, [ 'label' => $this->trans( 'Upload' ) ] )->getForm();

		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$file          = $form['module']->getData();
			$newModuleInfo = $this->_install( $file, $modulesService );

			if ( $newModuleInfo instanceof Response ) {
				return $newModuleInfo;
			}

			return $this->redirectToRoute(
				'syncengine_module_install_run',
				[
					'vendor'          => $newModuleInfo['vendor'],
					'moduleName'      => $newModuleInfo['moduleName'],
					'previousVersion' => $newModuleInfo['previousVersion'],
				]
			);
		}

		return $this->render(
			'admin/module/upload.html.twig',
			[
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_modules' ),
						'title' => $this->trans( 'Modules' ),
					],
					[
						'link'    => $this->generateUrl( 'syncengine_module_upload' ),
						'title'   => $this->trans( 'Upload' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/module/install/{vendor}/{moduleName}/{previousVersion}', name: 'module_install_run' )]
	public function moduleInstall( string $vendor, string $moduleName, string $previousVersion, Modules $modulesService )
	{
		$module = $modulesService->get( $vendor . "/" . $moduleName );

		if ( $previousVersion == 0 and $module->install() ) {
			$this->addFlash(
				'success',
				$this->trans( 'moduleName successfully installed', [ 'moduleName' => $moduleName ] )
			);
		} elseif ( $previousVersion != 0 and $module->update( $previousVersion ) ) {
			$this->addFlash(
				'success',
				$this->trans( 'moduleName successfully updated', [ 'moduleName' => $moduleName ] )
			);
		} else {
			$this->addFlash(
				'warning',
				$this->trans( 'Cant run install of moduleName', [ 'moduleName' => $moduleName ] )
			);
		}

		return $this->redirectToRoute( 'syncengine_modules' );
	}

	#[Route( '/module/uninstall/{vendor}/{module}', name: 'module_uninstall' )]
	public function uninstall( string $vendor, string $module, Request $request, Modules $modules, EntityManagerInterface $entityManager ): Response
	{
		$name   = $modules::getModulePackageName( $module, $vendor );
		$module = $modules->get( $name );

		if ( $this->activeSupervisors( $entityManager, $module ) ) {
			$this->addFlash(
				'warning',
				$this->trans(
					'Unable to uninstall {moduleName}. It is still used in your system',
					[ 'moduleName' => $name ]
				)
			);

			return $this->redirectToRoute( 'syncengine_modules' );
		}

		if ( $module->uninstall() ) {
			$this->addFlash(
				'success',
				$this->trans( '{moduleName} successfully uninstalled', [ 'moduleName' => $name ] )
			);
		} else {
			$this->addFlash( 'warning', $this->trans( 'Uninstall unsuccessful' ) );

			return $this->redirectToRoute( 'syncengine_modules' );
		}

		$filesystem = new Filesystem();

		$filesystem->remove( $this->getParameter( 'dir.modules' ) . DIRECTORY_SEPARATOR . $name );

		return $this->redirectToRoute( 'syncengine_modules' );
	}

	private function activeSupervisors( $entityManager, $module ): bool
	{
		$classes = [ "Automation", "Connection", "Flow", "Step", "Storage" ];
		foreach ( $classes as $class ) {
			if ( $entityManager->getRepository( "SyncEngine\Entity\\" . $class )->findBySupervisorClassLocator(
				$module->getClassLocator()
			) ) {
				return true;
			}
		}

		return false;
	}

	private function _install( $file, Modules $modulesService )
	{
		$newModuleInfo['moduleName'] = pathinfo( $file->getClientOriginalName(), PATHINFO_FILENAME );

		$this->_extract( $file );
		$newModuleInfo['vendor'] = $this->_validateModule( $newModuleInfo['moduleName'] );

		if ( $newModuleInfo['vendor'] instanceof Response ) {
			$this->_deleteTempFolder();

			return $newModuleInfo['vendor'];
		}

		$modules = $modulesService->getAll();

		$newClassLocator = $newModuleInfo['vendor'] . DIRECTORY_SEPARATOR . $newModuleInfo['moduleName'];
		foreach ( $modules as $module ) {
			if ( $newClassLocator == $module->getClassLocator() ) {
				$previousVersion = $module->getVersion();
				$this->deletePreviousVersion( $newClassLocator );
			}
		}

		$moduleDir  = $this->getParameter( 'dir.modules' );
		$filesystem = new Filesystem();
		$filesystem->mirror(
			$this->getParameter( 'dir.root' ) . DIRECTORY_SEPARATOR . '_tmp',
			$moduleDir
		);

		$moduleTranslationsFolder = $moduleDir
		                            . DIRECTORY_SEPARATOR
		                            . $newClassLocator
		                            . DIRECTORY_SEPARATOR
		                            . "translations";
		if ( $filesystem->exists( $moduleTranslationsFolder ) ) {
			$filesystem->mirror(
				$moduleTranslationsFolder,
				$this->getParameter( 'dir.translations' )
				. DIRECTORY_SEPARATOR
				. "modules"
				. DIRECTORY_SEPARATOR
				. $newClassLocator
			);
		}
		$this->_deleteTempFolder();

		$newModuleInfo['previousVersion'] = $previousVersion ?? 0;

		return $newModuleInfo;
	}

	private function deletePreviousVersion( $classLocator )
	{
		$filesystem  = new Filesystem();
		$dirLocation = $this->getParameter( 'dir.modules' ) . DIRECTORY_SEPARATOR . $classLocator;
		if ( $filesystem->exists( $dirLocation ) ) {
			$filesystem->remove( $dirLocation );
		}
	}

	private function _extract( $file )
	{
		$dir  = $this->getParameter( 'dir.root' );
		$name = $file->getClientOriginalName();

		try {
			$file->move( $dir, $name );
		} catch ( FileException $e ) {
			$this->addFlash( 'warning', $this->trans( 'Cant place file!' ) );

			return $this->redirectToRoute( 'syncengine_module_upload' );
		}

		$filesystem = new Filesystem();
		$zipfile    = $dir . DIRECTORY_SEPARATOR . $name;

		$zip = new \ZipArchive;
		if ( true === $zip->open( $zipfile ) ) {
			$zip->extractTo( $dir . DIRECTORY_SEPARATOR . '_tmp' );
			$zip->close();
		} else {
			$this->addFlash( 'warning', $this->trans( 'Cant unzip file!' ) );
		}

		$filesystem->remove( $zipfile );
	}

	private function _validateModule( $moduleName ): string|Response
	{
		$tempFolder        = $this->getParameter( 'dir.root' ) . DIRECTORY_SEPARATOR . '_tmp';
		$_tmpFolderContent = scandir( $tempFolder );

		$vendorName = $_tmpFolderContent[2];

		$moduleFolderContent = scandir( $tempFolder . DIRECTORY_SEPARATOR . $vendorName );

		if ( count( $_tmpFolderContent ) != 3 or count( $moduleFolderContent ) != 3 ) {
			$this->addFlash( 'warning', $this->trans( 'Module folder structure is not correct' ) );

			return $this->redirectToRoute( 'syncengine_module_upload' );
		}
		if ( $moduleFolderContent[2] != $moduleName ) {
			$this->addFlash( 'warning', $this->trans( 'Zip filename is incorrect.' ) );

			return $this->redirectToRoute( 'syncengine_module_upload' );
		}

		return $vendorName;
	}

	private function _deleteTempFolder()
	{
		$filesystem = new Filesystem();
		try {
			$filesystem->remove( $this->getParameter( 'dir.root' ) . DIRECTORY_SEPARATOR . '_tmp' );
		} catch ( FileException $e ) {
			$this->addFlash( 'warning', $this->trans( 'Cant delete temp folder' ) );
		}
	}
}
