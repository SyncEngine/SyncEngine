<?php

namespace SyncEngine\Controller\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Entity\User;
use SyncEngine\Form\ChangePasswordFormType;
use SyncEngine\Form\ResetPasswordRequestFormType;

#[Route( '/login/reset-password', name: 'reset_password_' )]
class ResetPasswordController extends DefaultController
{
	use ResetPasswordControllerTrait;

	public function __construct(
		private ResetPasswordHelperInterface $resetPasswordHelper,
		private EntityManagerInterface $entityManager,
		#[Autowire(param: 'syncengine.mailer.sender' )]
		protected readonly string $sender,
	) {}

	#[Route( '/', name: 'request' )]
	public function request( Request $request, MailerInterface $mailer, TranslatorInterface $translator ): Response
	{
		$form = $this->createForm( ResetPasswordRequestFormType::class );
		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			return $this->processSendingPasswordResetEmail( $form->get( 'email' )->getData(), $mailer, $translator );
		}

		return $this->render( 'auth/reset_password/request.html.twig', [
			'requestForm' => $form,
		] );
	}

	private function processSendingPasswordResetEmail( string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator ): RedirectResponse
	{
		$user = $this->entityManager->getRepository( User::class )->findOneBy( [
			'email' => $emailFormData,
		] );

		if ( ! $user ) {
			return $this->redirectToRoute( 'syncengine_reset_password_check_email' );
		}

		try {
			$resetToken = $this->resetPasswordHelper->generateResetToken( $user );
		} catch ( ResetPasswordExceptionInterface $e ) {
			return $this->redirectToRoute( 'syncengine_reset_password_check_email' );
		}

		$email = ( new TemplatedEmail() )->from( new Address( $this->sender, 'SyncEngine' ) )
		                                 ->to( $user->getEmail() )
		                                 ->subject( $this->trans( 'Your password reset request' ) )
		                                 ->htmlTemplate( 'auth/reset_password/email.html.twig' )
		                                 ->context( [
			                                 'resetToken' => $resetToken,
		                                 ] );

		$mailer->send( $email );

		$this->setTokenObjectInSession( $resetToken );

		return $this->redirectToRoute( 'syncengine_reset_password_check_email' );
	}

	/**
	 * Confirmation page after a user has requested a password reset.
	 */
	#[Route( '/check-email', name: 'check_email' )]
	public function checkEmail(): Response
	{
		if ( null === ( $resetToken = $this->getTokenObjectFromSession() ) ) {
			$resetToken = $this->resetPasswordHelper->generateFakeResetToken();
		}

		return $this->render( 'auth/reset_password/check_email.html.twig', [
			'resetToken' => $resetToken,
		] );
	}

	#[Route( '/reset/{token:token}', name: 'reset' )]
	public function reset( Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, ?string $token = null ): Response
	{
		if ( $token ) {
			$this->storeTokenInSession( $token );

			return $this->redirectToRoute( 'syncengine_reset_password_reset' );
		}

		$token = $this->getTokenFromSession();
		if ( null === $token ) {
			throw $this->createNotFoundException( $this->trans( 'No reset password token found in the URL or in the session.' ) );
		}

		try {
			$user = $this->resetPasswordHelper->validateTokenAndFetchUser( $token );
		} catch ( ResetPasswordExceptionInterface $e ) {
			$this->addFlash( 'reset_password_error', sprintf( '%s - %s', $translator->trans( ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle' ), $translator->trans( $e->getReason(), [], 'ResetPasswordBundle' ) ) );

			return $this->redirectToRoute( 'syncengine_reset_password_request' );
		}

		$form = $this->createForm( ChangePasswordFormType::class );
		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->resetPasswordHelper->removeResetRequest( $token );

			$encodedPassword = $passwordHasher->hashPassword( $user, $form->get( 'plainPassword' )->getData() );

			$user->setPassword( $encodedPassword );
			$this->entityManager->flush();

			$this->cleanSessionAfterReset();

			return $this->redirectToRoute( 'syncengine_admin_login' );
		}

		return $this->render( 'auth/reset_password/reset.html.twig', [
			'resetForm' => $form,
		] );
	}
}
