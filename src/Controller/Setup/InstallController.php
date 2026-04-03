<?php

namespace SyncEngine\Controller\Setup;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\Admin\SystemController;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Service\System;

class InstallController extends DefaultController
{
	#[Route( '/install', name: 'install' )]
	public function renderInstall(
		Request $request,
		EntityManagerInterface $entityManager,
		System $system,
		SystemController $systemController,
		LoggerInterface $syncengineLogger,
		KernelInterface $kernel,
	): Response {
		if ( true === $system->isInstalled( $entityManager ) ) {
			if ( true !== $system->isRegistered( $entityManager ) ) {
				return $this->redirectToRoute( 'syncengine_register' );
			}

			return $this->redirectToRoute( 'syncengine_admin_login' );
		}

		$env  = $system->getEnv();
		$form = $systemController->formEnv( $request, $env, $this->trans( 'Install' ) );

		try {
			// Check if a database is configured and the system is not installed yet.
			if ( $env->get( 'DATABASE_URL' ) && ! $system->isInstalled() ) {

				// Validate database connection.
				$dbConnected = $system->isDatabaseConnected( $entityManager, $env );

				if ( $dbConnected instanceof \Throwable ) {
					$this->addFlash( 'warning', $dbConnected->getMessage() );
					$syncengineLogger->error( $dbConnected );

				} elseif ( $dbConnected ) {
					// Install database schema.
					$success = $system->install( $entityManager, $env );

					if ( $success instanceof \Throwable ) {
						$this->addFlash( 'warning', $success->getMessage() );
						$syncengineLogger->error( $success );

					} else {
						// Check if installed successfully.
						$success = $system->isInstalled( $entityManager, $env );

						if ( true === $success ) {
							return $this->redirectToRoute( 'syncengine_register' );
						}

						if ( $success instanceof \Throwable ) {
							$this->addFlash( 'warning', $success->getMessage() );
							$syncengineLogger->error( $success );
						} else {
							$this->addFlash( 'warning', $this->trans( 'Unknown database error' ) );
						}
					}
				}
			}
		} catch ( \Throwable $e ) {
			if ( $kernel->isDebug() ) {
				throw $e;
			}
			$this->addFlash( 'warning', $e->getMessage() );
			$syncengineLogger->error( $e );
		}

		return $this->render( 'index.html.twig', [
			'header'      => $this->trans( 'Environment' ),
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'syncengine_system_index' ),
					'title' => $this->trans( 'System' ),
				],
				[
					'title'   => $this->trans( 'Environment' ),
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/install/repair', name: 'install_repair' )]
	public function handleRepair(
		Request $request,
		EntityManagerInterface $entityManager,
		System $system,
	): Response {

		// Reinstall.
		$response = $system->repairDatabase();

		if ( $system->isInstalled( $entityManager ) ) {
			return $this->redirectToRoute( 'syncengine_admin_index' );
		}

		if ( $response instanceof \Throwable ) {
			throw $response;
		} else {
			throw new \ErrorException( 'Could not repair database' );
		}
	}
}
