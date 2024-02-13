<?php

namespace SyncEngine\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Entity\User;
use SyncEngine\Form\AccountFormType;
use SyncEngine\Repository\UserRepository;

class AccountController extends DefaultController
{
	#[Route( '/account', name: 'account' )]
	public function renderEdit(
		Request $request,
		Security $security,
		EntityManagerInterface $entityManager,
		UserRepository $repository,
		UserPasswordHasherInterface $userPasswordHasher,
	): Response {
		$userIdentifier = $security->getUser()->getUserIdentifier();
		if ( ! $userIdentifier ) {
			return $this->redirectToRoute( 'app_index' );
		}
		$user = $repository->findOneBy( [ 'email' => $userIdentifier ] );

		if ( ! $user ) {
			return $this->redirectToRoute( 'app_index' );
		}

		return $this->render( 'admin/index.html.twig', [
			'header' => $this->trans( 'Account' ),
			'form'   => $this->formAccount( $user, $request, $entityManager, $userPasswordHasher ),
		] );
	}

	public function formAccount(
		User $user,
		Request $request,
		EntityManagerInterface $entityManager,
		UserPasswordHasherInterface $userPasswordHasher,
	) {
		$form = $this->createForm( AccountFormType::class, $user )
		             ->add( 'update', SubmitType::class, [ 'label' => $this->trans( 'Update' ) ] );

		$form->setData( [
			'email' => $user->getEmail(),
			'name'  => $user->getName(),
		] );

		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$newPassword = $form->get( 'plainPassword' )->getData();
			if ( ! empty( $newPassword ) ) {
				$user->setPassword( $userPasswordHasher->hashPassword( $user, $newPassword ) );
			}

			$entityManager->persist( $user );
			$entityManager->flush();
		}

		return $form;
	}
}
