<?php

namespace SyncEngine\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\File;
use SyncEngine\Attribute\MenuItem;
use SyncEngine\Model\ModuleModel;
use SyncEngine\Service\Provider\Modules;
use SyncEngine\Service\System;

class ModuleController extends AdminController
{

	public function __construct(
		private readonly System $system, private readonly KernelInterface $kernel,
	) {}

	#[Route( '/json/module/{vendor:vendor}/{module:module}', name: 'json_module' )]
	public function moduleJson( string $vendor, string $module, Request $request, Modules $modules ): Response
	{
		$module = $modules->get( $module, $vendor );

		$response = $module->handleRequest( $request );

		return ( $response instanceof Response ) ? $response : new Response();
	}

	#[Route( '/module/{vendor:vendor}/{module:module}', name: 'module' )]
	public function module( string $vendor, string $module, Request $request, Modules $modules ): Response
	{
		$module = $modules->get( $module, $vendor );

		$response = $module->renderRequest( $request );

		if ( is_array( $response ) ) {
			return $this->render( 'admin/index.html.twig', $response );
		}

		return ( $response instanceof Response ) ? $response : $this->redirectToRoute( 'syncengine_modules' );
	}

	#[Route( '/modules', name: 'modules' )]
	#[MenuItem( menu: 'main', route: 'syncengine_modules', label: 'Modules', icon: 'module' )]
	public function index( Modules $modulesService ): Response
	{
		$modules = $modulesService->getAll();

		foreach ( $modules as $key => $module ) {
			$modules[ $key ] = $module->normalize();

			// @todo Move to model?
			$ref                           = new \ReflectionClass( $module );
			$modules[ $key ]['_has_admin'] = ( $ref->getMethod( 'renderRequest' )->class === $module::class );
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
	public function moduleUpload( Request $request, Modules $modulesService ): Response
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
			$newModuleInfo = $this->_installModule( $file, $modulesService );

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

	#[Route( '/module/install/{vendor:vendor}/{moduleName:moduleName}/{previousVersion}', name: 'module_install_run' )]
	public function moduleInstall( string $vendor, string $moduleName, string $previousVersion, Modules $modulesService )
	{
		$module  = $modulesService->get( $vendor . '/' . $moduleName );
		$success = false;

		if ( ! $previousVersion ) {
			try {
				$success = $module->install();
				$msg     = '{moduleName} successfully installed';
			} catch ( \Throwable $e ) {
				$msg = $e->getMessage();
			}
		} elseif ( $previousVersion ) {
			try {
				$success = $module->update( $previousVersion );
				$msg     = '{moduleName} successfully updated';
			} catch ( \Throwable $e ) {
				$msg = $e->getMessage();
			}
		}

		if ( $success ) {
			$this->addFlash(
				'success',
				$this->trans( $msg, [ 'moduleName' => $moduleName ] )
			);
		} else {
			$this->addFlash( 'warning', $msg );
		}

		return $this->redirectToRoute( 'syncengine_modules' );
	}

	#[Route( '/module/uninstall/{vendor:vendor}/{module:module}', name: 'module_uninstall' )]
	public function moduleUninstall( string $vendor, string $module, Request $request, Modules $modules, EntityManagerInterface $entityManager ): Response
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

		try {
			$module->uninstall();
			$this->addFlash(
				'success',
				$this->trans( '{moduleName} successfully uninstalled', [ 'moduleName' => $name ] )
			);
		} catch ( \Throwable $e ) {
			$this->addFlash( 'warning', $this->trans( 'Uninstall unsuccessful' ) );

			return $this->redirectToRoute( 'syncengine_modules' );
		}

		$this->_deleteModule( $module );
		$this->system->runCommand( 'cache:clear' );

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

	private function _installModule( UploadedFile $zipFile, Modules $modulesService )
	{
		$zipName = pathinfo( $zipFile->getClientOriginalName(), PATHINFO_FILENAME );

		$tmpModuleDir = $this->_getTmpDir() . DIRECTORY_SEPARATOR . $zipName;

		try {
			$tmpModuleDir = $this->_extract( $zipFile, $tmpModuleDir );
		} catch ( \Exception $e ) {
			if ( $e instanceof FileException ) {
				$this->addFlash( 'warning', $this->trans( 'Cannot move file' ) . ': ' . $e->getMessage() );
			} else {
				$this->addFlash( 'warning', $e->getMessage() );
			}
		}

		$tmpModuleDir = $this->_findModuleRoot( $tmpModuleDir );
		if ( ! $tmpModuleDir ) {
			$this->_deleteTmpDir();
			$this->addFlash( 'warning', $this->trans( 'Could not find module in zip file' ) );

			return $this->redirectToRoute( 'syncengine_module_upload' );
		}

		$moduleInfo = $this->_parseModuleInfo( $tmpModuleDir );

		if ( ! $moduleInfo ) {
			$this->_deleteTmpDir();
			$this->addFlash( 'warning', $this->trans( 'Could not parse module namespace' ) );

			return $this->redirectToRoute( 'syncengine_module_upload' );
		}

		$this->system->runCommand( 'cache:clear' );

		$modules = $modulesService->getAll();

		$modulePath    = $moduleInfo['vendor'] . DIRECTORY_SEPARATOR . $moduleInfo['moduleName'];
		$moduleLocator = $moduleInfo['vendor'] . '/' . $moduleInfo['moduleName'];

		foreach ( $modules as $module ) {
			if ( $moduleLocator === $module->getClassLocator() ) {
				$previousVersion = $module->getVersion();

				// Delete previous version.
				$this->_deleteModule( $module );
			}
		}

		$moduleRoot = $this->getParameter( 'dir.modules' );
		$moduleDir  = $moduleRoot . DIRECTORY_SEPARATOR . $modulePath;

		$filesystem = new Filesystem();
		$filesystem->mirror(
			$tmpModuleDir,
			$moduleDir
		);

		$moduleTransDir = $moduleDir . DIRECTORY_SEPARATOR . "translations";

		if ( $filesystem->exists( $moduleTransDir ) ) {
			$transDir = $this->getParameter( 'dir.translations' ) . DIRECTORY_SEPARATOR . 'modules';

			$filesystem->mirror(
				$moduleTransDir,
				$transDir . DIRECTORY_SEPARATOR . $modulePath
			);
		}

		$this->kernel->generateRegistry();

		$this->_deleteTmpDir();

		$moduleInfo['previousVersion'] = $previousVersion ?? 0;

		return $moduleInfo;
	}

	private function _deleteModule( ModuleModel $module ): void
	{
		$filesystem  = new Filesystem();
		$dirLocation = $this->getParameter( 'dir.modules' ) . DIRECTORY_SEPARATOR . $module->getClassLocator();
		if ( $filesystem->exists( $dirLocation ) ) {
			$filesystem->remove( $dirLocation );
		}
	}

	private function _extract( $file, $targetDir ): string
	{
		$tmpDir = $this->_getTmpDir();
		$name   = $file->getClientOriginalName();

		$file->move( $tmpDir, $name );

		$filesystem = new Filesystem();
		$zipfile    = $tmpDir . DIRECTORY_SEPARATOR . $name;

		$zip = new \ZipArchive;
		if ( true === $zip->open( $zipfile ) ) {
			$zip->extractTo( $targetDir );
			$zip->close();
		} else {
			throw new \Exception( $this->trans( 'Cannot unzip file!' ) );
		}

		$filesystem->remove( $zipfile );

		return $targetDir;
	}

	private function _findModuleRoot( $dir )
	{
		$files = ( new Finder() )->depth( 0 )->in( $dir )->files()->name( '*.php' );

		if ( ! $files->count() ) {
			$dirs = ( new Finder() )->depth( 0 )->in( $dir )->directories();

			foreach ( $dirs as $dir ) {
				$_dir = $dir->getPathname();
				$files->in( $_dir );

				if ( $files->count() ) {
					// Found root PHP file!
					return $_dir;
				}
			}

			return false;
		}

		return $dir;
	}

	private function _parseModuleInfo( $dir ): false|array
	{
		$files = ( new Finder() )->in( $dir . "/src" )->files()->name( '*.php' );

		$ns = '';
		foreach ( $files as $file ) {
			if ( $file instanceof \SplFileInfo ) {
				$ns = $this->_extractNamespace( $file );

				// @todo Validate ModuleModel?
				if ( $ns ) {
					break;
				}
			}
		}

		$modulePackage = Modules::parseModulePackageName( $ns );

		if ( ! $modulePackage ) {
			return false;
		}

		return [
			'vendor'     => $modulePackage[0],
			'moduleName' => $modulePackage[1],
		];
	}

	private function _deleteTmpDir()
	{
		$filesystem = new Filesystem();
		try {
			$filesystem->remove( $this->_getTmpDir() );
		} catch ( FileException $e ) {
			$this->addFlash( 'warning', $this->trans( 'Cant delete temp folder' ) );
		}
	}

	private function _getTmpDir()
	{
		return $this->getParameter( 'dir.tmp' ) . DIRECTORY_SEPARATOR . '_installer';
	}

	private function _extractNamespace( string|\SplFileInfo $file ): ?string
	{
		$ns = null;
		if ( ! is_string( $file ) ) {
			$file = $file->getPathname();
		}
		$handle = fopen( $file, "r" );
		if ( $handle ) {
			while ( ( $line = fgets( $handle ) ) !== false ) {
				if ( str_starts_with( $line, 'namespace' ) ) {
					$parts = explode( ' ', $line );
					$ns    = rtrim( trim( $parts[1] ), ';' );
					break;
				}
			}
			fclose( $handle );
		}

		return $ns;
	}
}
