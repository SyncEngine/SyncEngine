<?php

namespace App\Controller\Setup;

use App\Controller\DefaultController;
use App\Controller\SystemController;
use App\Service\System;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class InstallController extends DefaultController
{
	#[Route( '/install', name: 'app_install' )]
	public function renderInstall(
		Request $request, TranslatorInterface $translator, EntityManagerInterface $entityManager, System $system, SystemController $systemController
	): Response {
		if ( $system->isInstalled( $entityManager ) ) {
			if ( ! $system->isRegistered( $entityManager ) ) {
				return $this->redirectToRoute( 'app_register' );
			}
			$this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Already installed.' );
		}

		$env  = $system->getEnv();
		$form = $systemController->formEnv( $request, $env, 'Install' );

		if ( $form->isSubmitted() && $form->isValid() ) {
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

		$hasDatabase = $env->get( 'DATABASE_URL' );
		if ( $hasDatabase ) {
			$entityManager->getConnection()->connect();

			if ( ! $entityManager->getConnection()->isConnected() ) {
				$this->addFlash( 'warning', 'Could not connect to datatbase.' );
			}
		}

		return $this->render( 'index.html.twig', [
			'header'      => $translator->trans( 'Environment' ),
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'system_index' ),
					'title' => 'System',
				],
				[
					'title'   => $translator->trans( 'Environment' ),
					'current' => true,
				],
			],
		] );
	}
}
