<?php

namespace App\Controller\Setup;

use App\Controller\DefaultController;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\Env;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends SetupController
{
	#[Route( '/register', name: 'app_register' )]
	public function renderRegister( Request $request, TranslatorInterface $translator, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager ): Response
	{
		if ( ! $this->isDatabaseInstalled( $entityManager ) ) {
			$this->redirectToRoute( 'install_index' );
		}

		if ( $this->isInstalled( $entityManager ) ) {
			$this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
		}

		$user = new User();
		$form = $this->createForm( RegistrationFormType::class, $user )
		             ->add( 'register', SubmitType::class, [ 'label' => 'Register' ] );

		// @todo Register extra users.

		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$user->setPassword( $userPasswordHasher->hashPassword( $user, $form->get( 'plainPassword' )->getData() ) );

			//$role = ( $existingUsers ) ? [ "ROLE_USER" ] : [ "ROLE_ADMIN" ];
			$user->setRoles( [ "ROLE_ADMIN" ] );

			$entityManager->persist( $user );
			$entityManager->flush();

			return $this->redirectToRoute( 'app_index' );
		}

		return $this->render( 'index.html.twig', [
			'header' => $translator->trans( 'Register' ),
			'form'   => $form,
		] );
	}
}
