<?php

namespace SyncEngine\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Entity\User;
use SyncEngine\Form\AccountFormType;

class AccountController extends DefaultController
{
	#[Route( '/account', name: 'account_index' )]
	public function renderAccount(): Response {
		return $this->render( 'admin/index.html.twig', [
			'header' => $this->trans( 'Account' ),
			'cards' => [
				'edit' => [
					'icon'   => 'person',
					'header' => $this->trans( 'Edit account' ),
					'body'   => $this->trans( 'Edit user account' ),
					'link'   => $this->generateUrl( 'account_edit' ),
				],
				'tokens' => [
					'icon'   => 'shield-lock',
					'header' => $this->trans( 'Access tokens' ),
					'body'   => $this->trans( 'Manage access tokens' ),
					'link'   => $this->generateUrl( 'account_tokens' ),
				],
			],
			'breadcrumbs' => [
				[
					'title'   => $this->trans( 'Account' ),
					'current' => true,
				],
			]
		] );
	}

	#[Route( '/account/tokens', name: 'account_tokens' )]
	public function renderTokens(): Response {
		$user = $this->getUser();

		return $this->render( 'admin/index.html.twig', [
			'backlink'    => true,
			'header'      => $this->trans( 'Access tokens' ),
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'account_index' ),
					'title' => $this->trans( 'Account' ),
				],
				[
					'title'   => $this->trans( 'Tokens' ),
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/account/edit', name: 'account_edit' )]
	public function renderEdit(
		Request $request,
		EntityManagerInterface $entityManager,
		UserPasswordHasherInterface $userPasswordHasher,
	): Response {
		$user = $this->getUser();

		$form = $this->formAccount( $user, $request, $entityManager, $userPasswordHasher );

		return $this->render( 'admin/index.html.twig', [
			'backlink'    => true,
			'header'      => $this->trans( 'Edit account' ),
			'form'        => $form->createView(),
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'account_index' ),
					'title' => $this->trans( 'Account' ),
				],
				[
					'title'   => $this->trans( 'Edit' ),
					'current' => true,
				],
			]
		] );
	}

	public function formAccount(
		UserInterface|User $user,
		Request $request,
		EntityManagerInterface $entityManager,
		UserPasswordHasherInterface $userPasswordHasher,
	): FormInterface {
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
