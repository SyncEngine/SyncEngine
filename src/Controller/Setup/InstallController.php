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

class InstallController extends AdminController
{
	#[Route( '/install', name: 'install_index' )]
	public function install( Request $request, TranslatorInterface $translator, Env $env, EntityManagerInterface $entityManager ): Response
	{
		$hasDatabase = $env->get( 'DATABASE_URL' );
		if ( $hasDatabase ) {
			$entityManager->getConnection()->connect();

			if ( $entityManager->getConnection()->isConnected() ) {
				$this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Already installed.' );
			} else {
				$this->addFlash( 'error', 'Could not connect to datatbase.' );
			}
		}

		$env->setType( 'local' );

		$form = $this->createForm( EnvironmentFormType::class )
		             ->add( 'submit', SubmitType::class, [ 'label' => 'Install' ] );

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			foreach ( $form->getData() as $key => $value ) {
				$env->set( $key, $value );
			}
			$env->persist();
		} else {
			$form->setData( $env->fetch() );
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
