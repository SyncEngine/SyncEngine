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
use SyncEngine\Attribute\MenuItem;
use SyncEngine\Controller\Admin\Abstract\EntityController;
use SyncEngine\Entity\ApiToken;
use SyncEngine\Entity\User;
use SyncEngine\Form\AccountFormType;
use SyncEngine\Form\ApiTokenFormType;
use SyncEngine\Service\Generator\Token;

class AccountController extends EntityController
{
	#[Route( '/account', name: 'account_index' )]
	#[MenuItem( menu: 'main', route: 'syncengine_account_index', label: 'Account', icon: 'account' )]
	public function renderAccount(): Response
	{
		return $this->render(
			'admin/index.html.twig',
			[
				'header'      => $this->trans( 'Account' ),
				'icon'        => 'person-circle',
				'cards'       => [
					'edit'   => [
						'icon'   => 'person-gear',
						'header' => $this->trans( 'Edit account' ),
						'body'   => $this->trans( 'Edit user account' ),
						'link'   => $this->generateUrl( 'syncengine_account_edit' ),
					],
					'tokens' => [
						'icon'   => 'shield-lock',
						'header' => $this->trans( 'API tokens' ),
						'body'   => $this->trans( 'Manage API tokens' ),
						'link'   => $this->generateUrl( 'syncengine_account_tokens' ),
					],
				],
				'breadcrumbs' => [
					[
						'title'   => $this->trans( 'Account' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/account/edit', name: 'account_edit' )]
	public function renderEdit(
		Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher,
	): Response {
		$user = $this->getUser();

		$form = $this->formAccount( $user, $request, $entityManager, $userPasswordHasher );

		return $this->render(
			'admin/index.html.twig',
			[
				'backlink'    => true,
				'header'      => $this->trans( 'Edit account' ),
				'icon'        => 'person-gear',
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_account_index' ),
						'title' => $this->trans( 'Account' ),
					],
					[
						'title'   => $this->trans( 'Edit' ),
						'current' => true,
					],
				],
			]
		);
	}

	public function formAccount(
		UserInterface|User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher,
	): FormInterface {
		$form = $this->createForm( AccountFormType::class, $user )->add(
			'update',
			SubmitType::class,
			[ 'label' => $this->trans( 'Update' ) ]
		);

		$form->setData(
			[
				'email' => $user->getEmail(),
				'name'  => $user->getName(),
			]
		);

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

	#[Route( '/account/tokens', name: 'account_tokens' )]
	public function renderTokens(): Response
	{
		$user = $this->getUser();

		$apiTokens = $user->getApiTokens();

		return $this->render(
			'admin/account/tokens.html.twig',
			[
				'backlink'    => true,
				'title'       => $this->trans( 'Access tokens' ),
				'icon'        => 'shield-lock',
				'tokens'      => $apiTokens,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_account_index' ),
						'title' => $this->trans( 'Account' ),
					],
					[
						'title'   => $this->trans( 'API Tokens' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/account/tokens/create', name: 'account_tokens_create' )]
	public function renderTokensCreate(
		Request $request, EntityManagerInterface $entityManager,
	): Response {
		$user = $this->getUser();

		$form = $this->formApiToken( new ApiToken(), $user, $request, $entityManager );

		if ( $form instanceof Response ) {
			return $form;
		}

		return $this->render(
			'admin/index.html.twig',
			[
				'backlink'    => true,
				'header'      => $this->trans( 'Create API token' ),
				'icon'        => 'shield-lock',
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_account_index' ),
						'title' => $this->trans( 'Account' ),
					],
					[
						'link'  => $this->generateUrl( 'syncengine_account_tokens' ),
						'title' => $this->trans( 'API Tokens' ),
					],
					[
						'title'   => $this->trans( 'Create' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/account/tokens/edit/{id}', name: 'account_tokens_edit' )]
	public function renderTokensEdit(
		ApiToken $apiToken, Request $request, EntityManagerInterface $entityManager,
	): Response {
		$user = $this->getUser();

		$form = $this->formApiToken( $apiToken, $user, $request, $entityManager );

		if ( $form instanceof Response ) {
			return $form;
		}

		return $this->render(
			'admin/index.html.twig',
			[
				'backlink'    => true,
				'header'      => $this->trans( 'Edit API token' ),
				'icon'        => 'shield-lock',
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_account_index' ),
						'title' => $this->trans( 'Account' ),
					],
					[
						'link'  => $this->generateUrl( 'syncengine_account_tokens' ),
						'title' => $this->trans( 'API Tokens' ),
					],
					[
						'title'   => $this->trans( 'Edit' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/account/tokens/delete/{id}', name: 'account_tokens_delete' )]
	public function renderTokensDelete(
		ApiToken $apiToken, EntityManagerInterface $entityManager,
	): Response {
		/** @var User $user */
		$user = $this->getUser();

		$user->removeApiToken( $apiToken );
		$entityManager->persist( $user );
		$entityManager->flush();

		return $this->redirectToRoute( 'syncengine_account_tokens' );
	}

	public function formApiToken(
		ApiToken $apiToken, User $user, Request $request, EntityManagerInterface $entityManager,
	): FormInterface|Response {
		if ( ! $apiToken->getToken() ) {
			$apiToken->setToken( ( new Token() )->generate() );
			$saveLabel = $this->trans( 'Create' );
		} else {
			$saveLabel = $this->trans( 'Update' );
		}

		$form = $this->createForm( ApiTokenFormType::class, $apiToken )->add(
			'update',
			SubmitType::class,
			[ 'label' => $saveLabel ]
		);

		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$apiToken->setUser( $user );
			$entityManager->persist( $apiToken );
			$entityManager->flush();

			return $this->redirectToRoute( 'syncengine_account_tokens' );
		}

		return $form;
	}
}
