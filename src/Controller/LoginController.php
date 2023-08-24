<?php

namespace App\Controller;

use App\Service\System;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends DefaultController
{
	#[Route( '/login', name: 'app_login' )]
	public function renderLogin(
		AuthenticationUtils $authenticationUtils,
		EntityManagerInterface $entityManager,
		System $system
	): Response
	{
		if ( ! $system->isRegistered( $entityManager ) ) {
			return $this->redirectToRoute( 'app_install' );
		}

		$error        = $authenticationUtils->getLastAuthenticationError();
		$lastUsername = $authenticationUtils->getLastUsername();

		return $this->render( 'login/index.html.twig', [
			'last_username' => $lastUsername,
			'error'         => $error,
		] );
	}
}
