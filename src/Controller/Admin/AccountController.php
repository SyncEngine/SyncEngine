<?php

namespace SyncEngine\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Entity\User;
use SyncEngine\Form\AccountFormType;
use SyncEngine\Repository\UserRepository;

class AccountController extends DefaultController
{
	public function getCurrentUser(
		Security $security,
		UserRepository $repository,
	): ?User {
		$userIdentifier = $security->getUser()->getUserIdentifier();
		if ( ! $userIdentifier ) {
			return null;
		}

		return $repository->findOneBy( [ 'email' => $userIdentifier ] );
	}

	#[Route( '/account', name: 'account_index' )]
	public function renderAccount(
		Request $request,
		Security $security,
		EntityManagerInterface $entityManager,
		UserRepository $repository,
	): Response {
		$user = $this->getCurrentUser( $security, $repository );

		if ( ! $user ) {
			return $this->redirectToRoute( 'app_index' );
		}

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
	public function renderTokens(
		Request $request,
		Security $security,
		EntityManagerInterface $entityManager,
		UserRepository $repository,
		UserPasswordHasherInterface $userPasswordHasher,
	): Response {
		$user = $this->getCurrentUser( $security, $repository );

		if ( ! $user ) {
			return $this->redirectToRoute( 'app_index' );
		}

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
		Security $security,
		EntityManagerInterface $entityManager,
		UserRepository $repository,
		UserPasswordHasherInterface $userPasswordHasher,
	): Response {
		$user = $this->getCurrentUser( $security, $repository );

		if ( ! $user ) {
			return $this->redirectToRoute( 'app_index' );
		}

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
		User $user,
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
