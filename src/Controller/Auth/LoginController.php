<?php

namespace SyncEngine\Controller\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Service\System;

class LoginController extends DefaultController
{
	#[Route( '/login', name: 'admin_login' )]
	public function renderLogin(
		AuthenticationUtils $authenticationUtils,
		EntityManagerInterface $entityManager,
		System $system
	): Response
	{
		if ( true !== $system->isRegistered( $entityManager ) ) {
			if ( true !== $system->isInstalled( $entityManager ) ) {
				return $this->redirectToRoute( 'app_install' );
			}
			return $this->redirectToRoute( 'app_register' );
		}

		$error        = $authenticationUtils->getLastAuthenticationError();
		$lastUsername = $authenticationUtils->getLastUsername();

		return $this->render( 'auth/login/index.html.twig', [
			'last_username' => $lastUsername,
			'error'         => $error,
		] );
	}

	#[Route( '/logout', name: 'admin_logout' )]
	public function logout( Security $security ): Response
	{
		$security->logout( false );

		return $this->redirectToRoute( 'admin_login' );
	}
}
