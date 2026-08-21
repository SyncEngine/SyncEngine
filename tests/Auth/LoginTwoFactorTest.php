<?php

namespace SyncEngine\Tests\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SyncEngine\Entity\TwoFactor;
use SyncEngine\Entity\User;
use SyncEngine\Tests\Fixture\TestUser;
use SyncEngine\Tests\Fixture\TestUserTwoFactor;
use SyncEngine\Tests\TestCase\WebTestCase;

class LoginTwoFactorTest extends WebTestCase
{
	private ?User $testUser = null;
	private ?string $totpSecret = null;
	private ?KernelBrowser $client = null;

	protected function setUp(): void
	{
		$this->client = static::createClient();
		$container    = $this->client->getContainer();

		/** @var EntityManagerInterface $em */
		$em = $container->get( 'doctrine' )->getManager();

		/** @var UserPasswordHasherInterface $passwordHasher */
		$passwordHasher = $container->get( 'security.password_hasher' );

		// Create a user with TOTP enabled and a known secret for testing.
		$this->testUser   = TestUser::getOrCreate( $em, $passwordHasher );
		$this->totpSecret = TestUserTwoFactor::generateTotpSecret();

		$this->testUser->getTwoFactorMethods()->clear();

		$twoFactor = new TwoFactor();
		$twoFactor->setUser( $this->testUser );
		$twoFactor->setType( 'totp' );
		$twoFactor->setSecret( $this->totpSecret );
		$twoFactor->setEnabled( true );

		$this->testUser->addTwoFactorMethod( $twoFactor );
		$this->testUser->setTwoFactorEnabled( true );

		$em->persist( $twoFactor );
		$em->persist( $this->testUser );
		$em->flush();
	}

	public function startLoginSequence(): Crawler
	{
		$result = $this->client->request( 'GET', '/login' );

		$this->assertResponseIsSuccessful();

		$form = $result->filter( 'form' )->first()->form();

		$this->client->submit(
			$form,
			[
				'_username' => $this->testUser->getEmail(),
				'_password' => TestUser::PASSWORD,
			]
		);

		return $this->client->followRedirect();
	}

	/**
	 * Test that a user without 2FA enrolled is redirected to normal login.
	 */
	public function testLogin2faRedirectWhenNoTotpEnrolled(): void
	{
		// Create a user without TOTP enabled.
		$container = static::getContainer();
		/** @var EntityManagerInterface $em */
		$em = $container->get( 'doctrine' )->getManager();

		$this->testUser->setTwoFactorEnabled( false );
		foreach ( $this->testUser->getTwoFactorMethods() as $method ) {
			$this->testUser->removeTwoFactorMethod( $method );
		}
		$em->persist( $this->testUser );
		$em->flush();

		$crawler = $this->startLoginSequence();

		$current_location = $crawler->getUri();
		$this->assertStringContainsString(
			'/admin',
			$current_location,
			'User without 2FA should be redirected to /admin'
		);

		// Accessing /login/2fa without TOTP enrolled should return a 403.
		$this->client->request( 'GET', '/login/2fa' );

		$this->assertGreaterThanOrEqual(
			400,
			$this->client->getResponse()->getStatusCode(),
			'Accessing /login/2fa without TOTP enrolled should return an error (4xx/5xx)'
		);
	}

	/**
	 * Test that a user with TOTP enabled sees the 2FA form.
	 */
	public function testLogin2faRendersFormForTotpUser(): void
	{
		$this->startLoginSequence();

		$this->assertTrue( $this->testUser->isTotpAuthenticationEnabled() );

		// Access the 2FA form directly (in real flow, this is redirected after password auth).
		$this->client->request( 'GET', '/login/2fa' );

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists( 'form' );

		// The auth code field name comes from the firewall config (default: _auth_code).
		$this->assertSelectorExists( 'input[name="_auth_code"]' );
	}

	/**
	 * Test that submitting a correct TOTP code completes authentication.
	 */
	public function testLogin2faCheckWithValidCode(): void
	{
		$this->startLoginSequence();

		$this->assertTrue( $this->testUser->isTotpAuthenticationEnabled() );

		// Get the CSRF token from the 2FA form.
		$crawler = $this->client->request( 'GET', '/login/2fa' );

		// Submit the 2FA check form using the form node directly.
		$form = $crawler->filter( 'form' )->first()->form();
		$form['_auth_code']->setValue( TestUserTwoFactor::generateTotpCode( $this->totpSecret ) );

		$this->client->submit( $form );

		// After successful 2FA, the user should be redirected.
		$this->assertResponseRedirects();

		$redirect = $this->client->getResponse()->headers->get( 'Location' );

		$this->assertStringNotContainsString(
			'2fa',
			$redirect,
			'After successful 2FA, should not redirect back to /login/2fa'
		);
	}

	/**
	 * Test that submitting an invalid TOTP code shows an error.
	 */
	public function testLogin2faCheckWithInvalidCode(): void
	{
		// Set up the TwoFactorToken to simulate post-password-auth state.
		$this->startLoginSequence();

		// Get the CSRF token from the 2FA form.
		$crawler = $this->client->request( 'GET', '/login/2fa' );

		// Submit with an invalid code using the form node directly.
		$form               = $crawler->filter( 'form' )->first()->form();
		$form['_auth_code'] = '000000'; // Invalid code

		$this->client->submit( $form );

		// Should stay on the 2FA form with an error message.
		$this->assertResponseRedirects();

		$redirect = $this->client->getResponse()->headers->get( 'Location' );
		$this->assertStringContainsString(
			'/login/2fa',
			$redirect,
			'After invalid 2FA, should redirect back to /login/2fa'
		);

		$crawler = $this->client->followRedirect();

		// The Scheb bundle uses 'invalid' as the message key for invalid codes.
		$alertContent = $crawler->filter( '.alert, .error, [class*="error"], [class*="invalid"]' )->first()->text();
		$this->assertStringContainsStringIgnoringCase( 'valid', $alertContent );
	}
}
