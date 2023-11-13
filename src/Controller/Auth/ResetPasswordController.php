<?php

namespace App\Controller\Auth;

use App\Controller\DefaultController;
use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route( '/reset-password' )]
class ResetPasswordController extends DefaultController
{
	use ResetPasswordControllerTrait;

	public function __construct(
		private ResetPasswordHelperInterface $resetPasswordHelper, private EntityManagerInterface $entityManager
	) {}

	#[Route( '', name: 'app_forgot_password_request' )]
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
			return $this->redirectToRoute( 'app_check_email' );
		}

		try {
			$resetToken = $this->resetPasswordHelper->generateResetToken( $user );
		} catch ( ResetPasswordExceptionInterface $e ) {
			return $this->redirectToRoute( 'app_check_email' );
		}

		$email = ( new TemplatedEmail() )->from( new Address( 'mailer@your-domain.com', 'SyncEngine' ) )
		                                 ->to( $user->getEmail() )
		                                 ->subject( $this->trans( 'Your password reset request' ) )
		                                 ->htmlTemplate( 'auth/reset_password/email.html.twig' )
		                                 ->context( [
			                                 'resetToken' => $resetToken,
		                                 ] );

		$mailer->send( $email );

		$this->setTokenObjectInSession( $resetToken );

		return $this->redirectToRoute( 'app_check_email' );
	}

	/**
	 * Confirmation page after a user has requested a password reset.
	 */
	#[Route( '/check-email', name: 'app_check_email' )]
	public function checkEmail(): Response
	{
		if ( null === ( $resetToken = $this->getTokenObjectFromSession() ) ) {
			$resetToken = $this->resetPasswordHelper->generateFakeResetToken();
		}

		return $this->render( 'auth/reset_password/check_email.html.twig', [
			'resetToken' => $resetToken,
		] );
	}

	#[Route( '/reset/{token}', name: 'app_reset_password' )]
	public function reset( Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, string $token = null ): Response
	{
		if ( $token ) {
			$this->storeTokenInSession( $token );

			return $this->redirectToRoute( 'app_reset_password' );
		}

		$token = $this->getTokenFromSession();
		if ( null === $token ) {
			throw $this->createNotFoundException( $this->trans( 'No reset password token found in the URL or in the session.' ) );
		}

		try {
			$user = $this->resetPasswordHelper->validateTokenAndFetchUser( $token );
		} catch ( ResetPasswordExceptionInterface $e ) {
			$this->addFlash( 'reset_password_error', sprintf( '%s - %s', $translator->trans( ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle' ), $translator->trans( $e->getReason(), [], 'ResetPasswordBundle' ) ) );

			return $this->redirectToRoute( 'app_forgot_password_request' );
		}

		$form = $this->createForm( ChangePasswordFormType::class );
		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->resetPasswordHelper->removeResetRequest( $token );

			$encodedPassword = $passwordHasher->hashPassword( $user, $form->get( 'plainPassword' )->getData() );

			$user->setPassword( $encodedPassword );
			$this->entityManager->flush();

			$this->cleanSessionAfterReset();

			return $this->redirectToRoute( 'app_login' );
		}

		return $this->render( 'auth/reset_password/reset.html.twig', [
			'resetForm' => $form,
		] );
	}
}
