<?php

namespace SyncEngine\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\Admin\Abstract\AbstractAdminController;
use SyncEngine\Entity\User;
use SyncEngine\Entity\TwoFactor;

class AccountTwoFactorController extends AbstractAdminController
{
	private readonly TotpAuthenticatorInterface $totpAuthenticator;
	private readonly UserPasswordHasherInterface $passwordHasher;
	private readonly MailerInterface $mailer;
	private readonly string $senderEmail;

	public function __construct(
		TotpAuthenticatorInterface $totpAuthenticator,
		UserPasswordHasherInterface $passwordHasher,
		MailerInterface $mailer,
		#[Autowire( '%env(SYNCENGINE_MAILER_SENDER)%' )] string $senderEmail,
	) {
		$this->totpAuthenticator = $totpAuthenticator;
		$this->passwordHasher    = $passwordHasher;
		$this->mailer            = $mailer;
		$this->senderEmail       = $senderEmail;
	}

	#[Route( '/account/2fa/setup', name: 'account_2fa_setup' )]
	public function setup( Request $request, EntityManagerInterface $entityManager ): Response
	{
		/** @var User $user */
		$user = $this->getUser();

		if ( ! $user instanceof User || $user->isTotpAuthenticationEnabled() ) {
			return $this->redirectToRoute( 'syncengine_account_index' );
		}

		// Generate a random base32 secret using the bundle's generator
		$secret = $this->totpAuthenticator->generateSecret();
		$uri    = $this->generateProvisioningUri( $user, $secret );

		return $this->render(
			'admin/account/2fa_setup.html.twig',
			[
				'backlink'         => true,
				'header'           => $this->trans( 'Enable two-factor authentication' ),
				'icon'             => 'two-factor',
				'secret'           => $secret,
				'provisioning_uri' => $uri,
				'qr_code'          => $this->generateQrCode( $uri ),
				'breadcrumbs'      => [
					[
						'link'  => $this->generateUrl( 'syncengine_account_index' ),
						'title' => $this->trans( 'Account' ),
					],
					[
						'title'   => $this->trans( 'Enable 2FA' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/account/2fa/totp-verify', name: 'account_2fa_totp_verify' )]
	public function verifyTotp( Request $request, EntityManagerInterface $entityManager ): Response
	{
		/** @var User $user */
		$user = $this->getUser();

		if ( ! $user instanceof User || $user->isTotpAuthenticationEnabled() ) {
			return $this->redirectToRoute( 'syncengine_account_index' );
		}

		$secret = $request->request->get( 'secret', '' );
		$code   = $request->request->get( '_auth_code', '' );

		if ( empty( $secret ) || empty( $code ) ) {
			return $this->redirectToRoute( 'syncengine_account_2fa_setup' );
		}

		// Verify CSRF token
		if ( ! $this->isCsrfTokenValid( 'verify_2fa', $request->request->get( '_csrf_token', '' ) ) ) {
			$this->addFlash( 'error', $this->trans( 'Invalid form submission. Please try again.' ) );

			return $this->redirectToRoute( 'syncengine_account_2fa_setup' );
		}

		// Create UserTwoFactor record
		$totpMethod = new TwoFactor();
		$totpMethod->setUser( $user );
		$totpMethod->setType( 'totp' );
		$totpMethod->setSecret( $secret );
		$totpMethod->setEnabled( true );
		$totpMethod->setVerifiedAt( new \DateTimeImmutable() );

		$user->addTwoFactorMethod( $totpMethod );
		$user->setTwoFactorEnabled( true );

		if ( ! $this->totpAuthenticator->checkCode( $user, $code ) ) {
			$this->addFlash( 'error', $this->trans( 'The verification code you entered is invalid.' ) );

			return $this->redirectToRoute( 'syncengine_account_2fa_setup' );
		}

		$entityManager->persist( $totpMethod );
		$entityManager->persist( $user );
		$entityManager->flush();

		$this->addFlash( 'success', $this->trans( 'Two-factor authentication has been enabled.' ) );

		return $this->redirectToRoute( 'syncengine_account_index' );
	}

	#[Route( '/account/2fa/email-setup', name: 'account_2fa_email_setup' )]
	public function emailSetup( Request $request, EntityManagerInterface $entityManager ): Response
	{
		/** @var User $user */
		$user = $this->getUser();

		if ( ! $user instanceof User || $user->isTotpAuthenticationEnabled() ) {
			return $this->redirectToRoute( 'syncengine_account_index' );
		}

		// Generate a 6-digit code
		$code = str_pad( random_int( 100000, 999999 ), 6, '0', STR_PAD_LEFT );

		$email = ( new Email() )->from( $this->senderEmail )->to( $user->getEmail() )->subject(
				'SyncEngine - Your 2FA Verification Code'
			)->html(
				'<p>Your verification code is: <strong>'
				. htmlspecialchars( $code )
				. '</strong></p>'
				. '<p>This code expires in 10 minutes.</p>'
			);

		try {
			$this->mailer->send( $email );
		} catch ( \Throwable $e ) {
			$this->addFlash( 'error', $this->trans( 'Could not send verification email. Please try again.' ) );

			return $this->redirectToRoute( 'syncengine_account_index' );
		}

		// Store code transiently in user settings for verification
		$user->setEmailAuthCode( $code );
		$entityManager->persist( $user );
		$entityManager->flush();

		return $this->render(
			'admin/account/2fa_email_verify.html.twig',
			[
				'backlink'    => true,
				'header'      => $this->trans( 'Verify your email' ),
				'icon'        => 'two-factor',
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_account_index' ),
						'title' => $this->trans( 'Account' ),
					],
					[
						'title'   => $this->trans( 'Verify Email' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/account/2fa/email-verify', name: 'account_2fa_email_verify' )]
	public function emailVerify( Request $request, EntityManagerInterface $entityManager ): Response
	{
		/** @var User $user */
		$user = $this->getUser();

		if ( ! $user instanceof User || $user->isTotpAuthenticationEnabled() ) {
			return $this->redirectToRoute( 'syncengine_account_index' );
		}

		$code = $request->request->get( '_auth_code', '' );

		if ( empty( $code ) ) {
			return $this->redirectToRoute( 'syncengine_account_email_2fa_setup' );
		}

		// Verify CSRF token
		if ( ! $this->isCsrfTokenValid( 'verify_2fa', $request->request->get( '_csrf_token', '' ) ) ) {
			$this->addFlash( 'error', $this->trans( 'Invalid form submission. Please try again.' ) );

			return $this->redirectToRoute( 'syncengine_account_email_2fa_setup' );
		}

		// Retrieve code from user settings (set during emailSetup)
		$storedCode = $user->getEmailAuthCode();

		if ( $code !== $storedCode ) {
			$this->addFlash( 'error', $this->trans( 'The verification code you entered is invalid.' ) );

			return $this->redirectToRoute( 'syncengine_account_email_2fa_setup' );
		}

		// Clear the used code
		$user->unsetEmailAuthCode();
		$user->setTwoFactorEnabled( true );

		$entityManager->persist( $user );
		$entityManager->flush();

		$this->addFlash( 'success', $this->trans( 'Two-factor authentication has been enabled.' ) );

		return $this->redirectToRoute( 'syncengine_account_index' );
	}

	#[Route( '/account/2fa/disable', name: 'account_2fa_disable' )]
	public function disableTotp( Request $request, EntityManagerInterface $entityManager ): Response
	{
		/** @var User $user */
		$user = $this->getUser();

		if ( ! $user instanceof User || ! $user->isTwoFactorEnabled() ) {
			return $this->redirectToRoute( 'syncengine_account_index' );
		}

		// Require current password confirmation before disabling 2FA
		$password = $request->request->get( 'password', '' );

		if ( empty( $password ) ) {
			return $this->render(
				'admin/account/2fa_disable_confirm.html.twig',
				[
					'backlink'    => true,
					'header'      => $this->trans( 'Disable two-factor authentication' ),
					'icon'        => 'two-factor',
					'breadcrumbs' => [
						[
							'link'  => $this->generateUrl( 'syncengine_account_index' ),
							'title' => $this->trans( 'Account' ),
						],
						[
							'title'   => $this->trans( 'Disable 2FA' ),
							'current' => true,
						],
					],
				]
			);
		}

		// Verify CSRF token
		if ( ! $this->isCsrfTokenValid( 'disable_2fa', $request->request->get( '_csrf_token', '' ) ) ) {
			$this->addFlash( 'error', $this->trans( 'Invalid form submission. Please try again.' ) );

			return $this->redirectToRoute( 'syncengine_account_2fa_disable' );
		}

		// Verify password
		if ( ! $this->passwordHasher->isPasswordValid( $user, $password ) ) {
			$this->addFlash( 'error', $this->trans( 'Invalid password.' ) );

			return $this->redirectToRoute( 'syncengine_account_2fa_disable' );
		}

		// Remove 2FA method
		foreach ( $user->getTwoFactorMethods() as $method ) {
			$user->removeTwoFactorMethod( $method );
			$entityManager->remove( $method );
			break;
		}

		// Update master toggle
		$user->setTwoFactorEnabled( false );

		$entityManager->persist( $user );
		$entityManager->flush();

		$this->addFlash( 'success', $this->trans( 'Two-factor authentication has been disabled.' ) );

		return $this->redirectToRoute( 'syncengine_account_index' );
	}

	private function generateProvisioningUri( User $user, string $secret ): string
	{
		$label  = $user->getUserIdentifier();
		$issuer = 'SyncEngine';

		return sprintf(
			'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=6&period=30&algorithm=SHA1',
			rawurlencode( $issuer ),
			rawurlencode( $label ),
			rawurlencode( $secret ),
			rawurlencode( $issuer )
		);
	}

	private function generateQrCode( string $data ): string
	{
		$qrCode = new QrCode(
			data                : $data,
			encoding            : new Encoding( 'UTF-8' ),
			errorCorrectionLevel: ErrorCorrectionLevel::High,
			size                : 300,
			margin              : 10,
			foregroundColor     : new Color( 0, 0, 0 ),
			backgroundColor     : new Color( 255, 255, 255 ),
		);

		$writer = new PngWriter();
		$result = $writer->write( $qrCode );

		return 'data:image/png;base64,' . base64_encode( $result->getString() );
	}
}
