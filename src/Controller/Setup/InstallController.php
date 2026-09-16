<?php

namespace SyncEngine\Controller\Setup;

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
		System $system,
		SystemController $systemController,
		LoggerInterface $syncengineLogger,
		KernelInterface $kernel,
	): Response {

		$env      = $system->getEnv();
		$response = new Response();

		if ( true === $system->isInstalled() ) {
			$env->update( 'SYNCENGINE_INSTALLED', 1 );

			if ( true !== $system->isRegistered() ) {
				return $this->redirectToRoute( 'syncengine_register' );
			}

			return $this->redirectToRoute( 'syncengine_admin_login' );
		}

		if ( $env->get( 'SYNCENGINE_INSTALLED' ) ) {
			// @todo Redirect to a different route?
			$this->addFlash( 'warning', $this->trans( 'The installer has already been completed and is no longer available.' ) );

			$connected = $system->isDatabaseConnected();
			if ( $connected instanceof \Throwable ) {
				$syncengineLogger->error( $connected );
				if ( $kernel->isDebug() ) {
					$this->addFlash( 'warning', $connected->getMessage() );
				} else {
					$this->addFlash( 'warning', $this->trans( 'Could not connect to the database. Please verify your configuration.' ) );
				}
			}

			$response->setStatusCode( Response::HTTP_FORBIDDEN );

			return $this->render('index.html.twig', [], $response );
		}

		$form = $systemController->formEnv( $request, $env, $this->trans( 'Install' ) );

		try {
			// Check if a database is configured and the system is not installed yet.
			if ( $env->get( 'DATABASE_URL' ) && ! $system->isInstalled() ) {

				// Validate database connection.
				$dbConnected = $system->isDatabaseConnected();

				if ( $dbConnected instanceof \Throwable ) {
					$this->addFlash( 'warning', $dbConnected->getMessage() );
					$syncengineLogger->error( $dbConnected );
					$response->setStatusCode( Response::HTTP_UNPROCESSABLE_ENTITY );

				} elseif ( $dbConnected ) {
					// Install database schema.
					$success = $system->install();

					if ( $success instanceof \Throwable ) {
						$this->addFlash( 'warning', $success->getMessage() );
						$syncengineLogger->error( $success );
						$response->setStatusCode( Response::HTTP_UNPROCESSABLE_ENTITY );

					} else {
						// Check if installed successfully.
						$success = $system->isInstalled();

						if ( true === $success ) {
							return $this->redirectToRoute( 'syncengine_register' );
						}

						if ( $success instanceof \Throwable ) {
							$this->addFlash( 'warning', $success->getMessage() );
							$syncengineLogger->error( $success );
						} else {
							$this->addFlash( 'warning', $this->trans( 'Unknown database error' ) );
							$response->setStatusCode( Response::HTTP_UNPROCESSABLE_ENTITY );
						}
					}
				}
			} elseif ( $form->isSubmitted() ) {
				$response->setStatusCode( Response::HTTP_UNPROCESSABLE_ENTITY );
			}
		} catch ( \Throwable $e ) {
			if ( $kernel->isDebug() ) {
				throw $e;
			}
			$this->addFlash( 'warning', $e->getMessage() );
			$syncengineLogger->error( $e );
			$response->setStatusCode( Response::HTTP_UNPROCESSABLE_ENTITY );
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
		], $response );
	}

	#[Route( '/install/repair', name: 'install_repair' )]
	public function handleRepair(
		Request $request,
		System $system,
	): Response {
		$error = $request->query->get( 'error' ) ?: '';

		// Reinstall.
		$response = $system->repairDatabase();

		if ( true === $response && $system->isInstalled() ) {

			if ( $request->query->get( 'redirect_to' ) ) {
				return $this->redirect( $request->query->get( 'redirect_to' ) );
			}

			return $this->redirectToRoute( 'syncengine_admin_index' );
		}

		$this->addFlash( 'warning', $error );

		if ( $response instanceof \Throwable ) {
			throw $response;
		}

		return $this->render( 'index.html.twig', [
			'header' => $this->trans( 'Could not repair database' ),
		] );
	}
}
