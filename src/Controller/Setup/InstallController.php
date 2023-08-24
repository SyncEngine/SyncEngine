<?php

namespace App\Controller\Setup;

use App\Controller\DefaultController;
use App\Controller\SystemController;
use App\Service\System;
use Composer\XdebugHandler\Process;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class InstallController extends DefaultController
{
	#[Route( '/install', name: 'app_install' )]
	public function renderInstall(
		Request $request,
		TranslatorInterface $translator,
		EntityManagerInterface $entityManager,
		System $system,
		SystemController $systemController
	): Response {
		if ( true === $system->isInstalled( $entityManager ) ) {
			if ( true !== $system->isRegistered( $entityManager ) ) {
				return $this->redirectToRoute( 'app_register' );
			}
			return $this->redirectToRoute( 'app_login' );
		}

		$env  = $system->getEnv();
		if ( ! $env->get('APP_SECRET') ) {
			$env->set( 'APP_SECRET', bin2hex( random_bytes( 20 ) ) );
			$env->persist();
		}

		$form = $systemController->formEnv( $request, $env, 'Install' );

		if ( $env->get( 'DATABASE_URL' ) ) {

			if ( ! $system->isDatabaseInstalled( $entityManager, $env ) ) {
				$this->addFlash( 'warning', 'Could not connect to database.' );
			} else {
				try {
					$success = $system->install( $entityManager, $env );
					if ( $success instanceof \Throwable ) {
						$this->addFlash( 'warning', $success->getMessage() );
					} else {
						$success = $system->isInstalled( $entityManager, $env );
						if ( true === $success ) {
							return $this->redirectToRoute( 'app_register' );
						}
						if ( $success instanceof \Throwable ) {
							$this->addFlash( 'warning', $success->getMessage() );
						} else {
							$this->addFlash( 'warning', 'Unknown database error' );
						}
					}
				} catch ( \Throwable $e ) {
					$this->addFlash( 'warning', $e->getMessage() );
				}
			}
		}

		return $this->render( 'index.html.twig', [
			'header'      => $translator->trans( 'Environment', [], 'core' ),
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'system_index' ),
					'title' => 'System',
				],
				[
					'title'   => $translator->trans( 'Environment', [], 'core' ),
					'current' => true,
				],
			],
		] );
	}
}
