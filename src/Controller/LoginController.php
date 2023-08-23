<?php

namespace App\Controller;

use App\Controller\Setup\SetupController;
use App\Service\Env;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends DefaultController
{
	#[Route( '/login', name: 'app_login' )]
	public function renderLogin( AuthenticationUtils $authenticationUtils, Env $env, EntityManagerInterface $entityManager ): Response
	{
		$setup = new SetupController( $env );
		if ( ! $setup->isInstalled( $entityManager ) ) {
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
