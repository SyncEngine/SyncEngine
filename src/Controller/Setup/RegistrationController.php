<?php

namespace SyncEngine\Controller\Setup;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Entity\User;
use SyncEngine\Form\RegistrationFormType;
use SyncEngine\Service\System;

class RegistrationController extends DefaultController
{
	#[Route( '/register', name: 'register' )]
	public function renderRegister(
		Request $request,
		UserPasswordHasherInterface $userPasswordHasher,
		EntityManagerInterface $entityManager,
		System $system
	): Response
	{
		if ( true !== $system->isInstalled( $entityManager ) ) {
			return $this->redirectToRoute( 'syncengine_install' );
		}

		$existingUsers = $system->isRegistered( $entityManager );
		if ( $existingUsers ) {
			$this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, $this->trans( 'Unable to access this page!' ) );
		}

		$user = new User();
		$form = $this->createForm( RegistrationFormType::class, $user )
		             ->add( 'register', SubmitType::class, [ 'label' => $this->trans( 'Register' ) ] );

		// @todo Register other users?

		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$user->setPassword( $userPasswordHasher->hashPassword( $user, $form->get( 'plainPassword' )->getData() ) );

			/* todo: If we have role support in SyncEngine add selectbox for new user roles*/
			//$role = ( $existingUsers ) ? [ "ROLE_USER" ] : [ "ROLE_ADMIN" ];
			$user->setRoles( [ "ROLE_ADMIN" ] );

			$entityManager->persist( $user );
			$entityManager->flush();

			return $this->redirectToRoute( 'syncengine_admin_index' );
		}

		return $this->render( 'index.html.twig', [
			'header' => $this->trans( 'Register' ),
			'form'   => $form,
		] );
	}
}
