<?php

namespace SyncEngine\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use SyncEngine\Attribute\MenuItem;
use SyncEngine\Controller\Auth\ResetPasswordController;
use SyncEngine\Entity\User;
use SyncEngine\Form\UserFormType;
use SyncEngine\Repository\UserRepository;

#[IsGranted( 'ROLE_ADMIN' )]
class UserController extends AdminController
{
	#[Route( '/users', name: 'users_list' )]
	#[MenuItem( menu: 'main', route: 'syncengine_users_list', label: 'Users', icon: 'system-users' )]
	public function renderList( Request $request, UserRepository $userRepository ): Response
	{
		$users = $userRepository->findAll();

		return $this->render(
			'admin/user/index.html.twig',
			[
				'users'       => $users,
				'breadcrumbs' => [ [ 'title' => $this->trans( 'Users' ), 'current' => true ] ],
			]
		);
	}

	#[Route( '/user/create', name: 'user_create' )]
	public function renderCreate(
		Request $request,
		UserPasswordHasherInterface $passwordHasher,
		EntityManagerInterface $entityManager,
	): Response {
		$user = new User();
		$form = $this->createForm( UserFormType::class, $user );
		$form->add( 'save', SubmitType::class, [ 'label' => $this->trans( 'Create' ) ] );
		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$newPassword = $form->get( 'plainPassword' )->getData();
			if ( $newPassword ) {
				$user->setPassword( $passwordHasher->hashPassword( $user, $newPassword ) );
			}

			$entityManager->persist( $user );
			$entityManager->flush();

			$this->addFlash( 'success', $this->trans( 'Successfully created user!' ) );

			return $this->redirectToRoute( 'syncengine_users_list' );
		}

		return $this->render(
			'admin/user/create.html.twig',
			[
				'form'        => $form,
				'backlink'    => true,
				'breadcrumbs' => [
					[ 'link' => $this->generateUrl( 'syncengine_users_list' ), 'title' => $this->trans( 'Users' ) ],
					[ 'title' => $this->trans( 'Create' ), 'current' => true ],
				],
			]
		);
	}

	#[Route( '/user/edit/{id}', name: 'user_edit' )]
	public function renderEdit(
		#[MapEntity( id: 'id' )]
		User $user, Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager
	): Response {
		$form = $this->createForm( UserFormType::class, $user );
		$form->add( 'save', SubmitType::class, [ 'label' => $this->trans( 'Update' ) ] );
		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$newPassword = $form->get( 'plainPassword' )->getData();
			if ( $newPassword ) {
				$user->setPassword( $passwordHasher->hashPassword( $user, $newPassword ) );
			}

			$entityManager->persist( $user );
			$entityManager->flush();

			$this->addFlash( 'success', $this->trans( 'Successfully edited user!' ) );

			return $this->redirectToRoute( 'syncengine_users_list' );
		}

		return $this->render(
			'admin/user/edit.html.twig',
			[
				'form'        => $form,
				'user'        => $user,
				'backlink'    => true,
				'breadcrumbs' => [
					[ 'link' => $this->generateUrl( 'syncengine_users_list' ), 'title' => $this->trans( 'Users' ) ],
					[ 'title' => $this->trans( 'Edit' ), 'current' => true ],
				],
			]
		);
	}

	#[Route( '/user/reset-password/{id}', name: 'user_reset_password', methods: [ 'POST' ] )]
	public function resetPasswordAction(
		#[MapEntity( id: 'id' )]
		User $user, Request $request, ResetPasswordController $resetPasswordController
	): RedirectResponse {
		$currentUser = $this->getUser();
		if ( $currentUser instanceof User && $currentUser->getId() === $user->getId() ) {
			$this->addFlash( 'warning', $this->trans( 'You cannot reset your own password.' ) );

			return $this->redirectToRoute( 'syncengine_users_list' );
		}

		if ( $this->isCsrfTokenValid( 'reset_' . $user->getId(), $request->request->get( '_token' ) ) ) {
			try {
				$resetPasswordController->processSendingPasswordResetEmail( $user );
				$this->addFlash( 'success', $this->trans( 'Password reset email sent successfully!' ) );
			} catch ( \Exception $e ) {
				$this->addFlash( 'error', $this->trans( 'An error occurred while resetting the password.' ) );
			}
		}

		return $this->redirectToRoute( 'syncengine_users_list' );
	}

	#[Route( '/user/delete/{id}', name: 'user_delete', methods: [ 'POST' ] )]
	public function deleteAction(
		#[MapEntity( id: 'id' )]
		User $user, Request $request, EntityManagerInterface $entityManager
	): RedirectResponse {
		$currentUser = $this->getUser();
		if ( $currentUser instanceof User && $currentUser->getId() === $user->getId() ) {
			$this->addFlash( 'warning', $this->trans( 'You cannot delete your own account.' ) );

			return $this->redirectToRoute( 'syncengine_users_list' );
		}

		if ( $this->isCsrfTokenValid( 'delete_' . $user->getId(), $request->request->get( '_token' ) ) ) {
			$entityManager->remove( $user );
			$entityManager->flush();

			$this->addFlash( 'success', $this->trans( 'Successfully deleted user!' ) );
		}

		return $this->redirectToRoute( 'syncengine_users_list' );
	}
}
