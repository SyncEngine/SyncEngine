<?php

namespace App\Controller\Setup;

use App\Controller\AdminController;
use App\Controller\DefaultController;
use App\Controller\SystemController;
use App\Entity\User;
use App\Form\EnvironmentFormType;
use App\Service\Env;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class InstallController extends SetupController
{
	#[Route( '/install', name: 'app_install' )]
	public function renderInstall( Request $request, TranslatorInterface $translator, EntityManagerInterface $entityManager, SystemController $systemController ): Response
	{
		if ( $this->isDatabaseInstalled( $entityManager ) ) {
			if ( $this->isInstalled( $entityManager ) ) {
				$this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Already installed.' );
			}
		}

		$form = $systemController->formEnv( $request, $this->env, 'Install' );

		if ( $form->isSubmitted() && $form->isValid() ) {
			try {
				$this->install( $entityManager, $this->env );
				return $this->redirectToRoute( 'app_register' );
			} catch ( \Throwable $e ) {
				$this->addFlash( 'warning', $e->getMessage() );
			}
		}

		$hasDatabase = $this->env->get( 'DATABASE_URL' );
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
