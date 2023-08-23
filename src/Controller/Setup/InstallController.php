<?php

namespace App\Controller\Setup;

use App\Controller\AdminController;
use App\Controller\DefaultController;
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
	#[Route( '/install', name: 'install_index' )]
	public function renderInstall( Request $request, TranslatorInterface $translator, EntityManagerInterface $entityManager ): Response
	{
		if ( $this->isDatabaseInstalled( $entityManager ) ) {
			$this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Already installed.' );
		}

		$form = $this->createForm( EnvironmentFormType::class )
		             ->add( 'submit', SubmitType::class, [ 'label' => 'Install' ] );

		$form->handleRequest( $request );
		$env = $this->env;
		if ( $form->isSubmitted() && $form->isValid() ) {
			foreach ( $form->getData() as $key => $value ) {
				$env->set( $key, $value );
			}
			$env->persist();

			try {
				$this->install( $entityManager, $env );
			} catch ( \Throwable $e ) {
				$this->addFlash( 'warning', $e->getMessage() );
			}

		} else {
			$form->setData( $env->fetch() );
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
