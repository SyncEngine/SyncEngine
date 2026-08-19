<?php

namespace SyncEngine\Tests\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SyncEngine\Entity\User;
use SyncEngine\Entity\TwoFactor;
use SyncEngine\Tests\Fixture\TestUser;

class AccountTwoFactorTest extends WebTestCase
{
    private ?User $testUser = null;
    private ?string $totpSecret = null;
    private ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = $this->client->getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get( 'doctrine' )->getManager();

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get( 'security.password_hasher' );

        $this->testUser = TestUser::getOrCreate( $em, $passwordHasher );

        // Ensure TOTP is disabled for this test by removing any existing methods.
        if ( $this->testUser->isTwoFactorEnabled() ) {
            foreach ( $this->testUser->getTwoFactorMethods() as $method ) {
                $em->remove( $method );
            }
            $this->testUser->getTwoFactorMethods()->clear();
            $this->testUser->setTwoFactorEnabled( false );
            $em->persist( $this->testUser );
            $em->flush();
        }

        $this->client->loginUser( $this->testUser, 'main' );
    }

    /**
     * Test that requesting TOTP setup generates a QR code and secret.
     */
    public function testTotpSetupGeneratesQrCode(): void
    {
        $crawler = $this->client->request( 'GET', '/admin/account/2fa/setup' );

        $this->assertResponseIsSuccessful();

        // Should contain a QR code or secret key display.
        $qrCode = $crawler->filter( 'img[src*="data:image"]' )->count();
        $secretField = $crawler->filter( 'code' )->count();

        $this->assertGreaterThan( 0, $qrCode + $secretField,
            'TOTP setup page should display QR code or secret key' );
    }

    /**
     * Test that submitting a correct verification code enables TOTP.
     */
    public function testTotpSetupWithValidCode(): void
    {
        // First, get the setup form to extract the generated secret.
        $crawler = $this->client->request( 'GET', '/admin/account/2fa/setup' );

        // Find the TOTP verify form by looking for a form with a secret input.
        $totpFormNode = null;
        $formCount = $crawler->filter( 'form' )->count();

        $crawler->filter( 'form' )->each( function ( Crawler $node ) use ( &$totpFormNode ) {
            if ( $node->filter( 'input[name="secret"]' )->count() > 0 ) {
                $totpFormNode = $node;
            }
        } );

        $this->assertNotNull( $totpFormNode, 'TOTP verify form not found. Form count: ' . $formCount );

        // Extract the secret from the hidden input in the form.
        $secretInput = $totpFormNode->filter( 'input[name="secret"]' )->first();
        $this->assertGreaterThan( 0, $secretInput->count(), 'Secret input not found in TOTP verify form' );

        $this->totpSecret = $secretInput->attr( 'value' );
        $this->assertNotNull( $this->totpSecret );

        // Generate a valid code for the current time window.
        $validCode = $this->generateTotpCode( $this->totpSecret );

        $form = $totpFormNode->form();
        $form['_auth_code'] = $validCode;
        $form['secret'] = $this->totpSecret;

        // Get CSRF token from the form.
        $csrfInput = $totpFormNode->filter( 'input[name="_csrf_token"]' )->first();
        if ( $csrfInput->count() > 0 ) {
            $form['_csrf_token'] = $csrfInput->attr( 'value' );
        }

        $this->client->submit( $form );

        // After successful verification, should redirect to account index.
        $this->assertResponseRedirects( '/admin/account', 302 );

        // Verify TOTP is now enabled for the user by fetching fresh from DB.
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get( 'doctrine' )->getManager();
        $freshUser = $em->getRepository( User::class )->find( $this->testUser->getId() );

        $this->assertTrue( $freshUser->isTwoFactorEnabled(), 'TwoFactor should be enabled after verification' );
        $this->assertTrue( $freshUser->isTotpAuthenticationEnabled(), 'TOTP should be enabled after verification' );
    }

    /**
     * Test that submitting an incorrect verification code fails gracefully.
     */
    public function testTotpSetupWithInvalidCode(): void
    {
        // Get the setup form first (to get CSRF token and secret).
        $crawler = $this->client->request( 'GET', '/admin/account/2fa/setup' );

        // Find the TOTP verify form by looking for a form with a secret input.
        $totpFormNode = null;
        $formCount = $crawler->filter( 'form' )->count();

        $crawler->filter( 'form' )->each( function ( Crawler $node ) use ( &$totpFormNode ) {
            if ( $node->filter( 'input[name="secret"]' )->count() > 0 ) {
                $totpFormNode = $node;
            }
        } );

        $this->assertNotNull( $totpFormNode, 'TOTP verify form not found. Form count: ' . $formCount );

        // Extract the secret from the hidden input.
        $secretInput = $totpFormNode->filter( 'input[name="secret"]' )->first();
        $this->assertGreaterThan( 0, $secretInput->count(), 'Secret input not found in TOTP verify form' );

        $secret = $secretInput->attr( 'value' );

        $form = $totpFormNode->form();
        $form['_auth_code'] = '000000';
        $form['secret'] = $secret;

        // Get CSRF token from the form.
        $csrfInput = $totpFormNode->filter( 'input[name="_csrf_token"]' )->first();
        if ( $csrfInput->count() > 0 ) {
            $form['_csrf_token'] = $csrfInput->attr( 'value' );
        }

        $this->client->submit( $form );

        // Should redirect back to setup with error flash.
        $this->assertResponseRedirects( '/admin/account/2fa/setup', 302 );
    }

    /**
     * Test that disabling TOTP removes the method.
     */
    public function testTotpDisableRemovesMethod(): void
    {
        // First, enable TOTP for this user.
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get( 'doctrine' )->getManager();

        // Get a managed entity from the current EM.
        $managedUser = $em->getRepository( User::class )->find( $this->testUser->getId() );

        $totpSecret = bin2hex( random_bytes( 20 ) );
        $twoFactor = new TwoFactor();
        $twoFactor->setUser( $managedUser );
        $twoFactor->setType( 'totp' );
        $twoFactor->setSecret( $totpSecret );
        $twoFactor->setEnabled( true );

        $managedUser->addTwoFactorMethod( $twoFactor );
        $managedUser->setTwoFactorEnabled( true );
        $em->persist( $twoFactor );
        $em->flush();

        // Verify TOTP is enabled.
        $this->assertTrue( $managedUser->isTwoFactorEnabled() );
        $this->assertTrue( $managedUser->isTotpAuthenticationEnabled() );

        // Get the CSRF token from the disable form.
        $crawler = $this->client->request( 'GET', '/admin/account/2fa/disable' );
        $csrfToken = $this->extractCsrfToken( $crawler );

        // Now submit the disable form with password.
        $formNode = $crawler->filter( 'form[action*="account/2fa/disable"]' )->first();
        $this->assertGreaterThan( 0, $formNode->count(), 'Form not found on disable page' );

        $form = $formNode->form();
        $form['password'] = TestUser::PASSWORD;
        $form['_csrf_token'] = $csrfToken;

        $this->client->submit( $form );

        // Get fresh user from DB.
        $freshUser = $em->getRepository( User::class )->find( $managedUser->getId() );

        // Verify TOTP is disabled.
        $this->assertFalse( $freshUser->isTotpAuthenticationEnabled(),
            'TOTP should be disabled after disable action' );
    }

    /**
     * Test that unauthenticated users cannot access account 2FA routes.
     */
    public function testUnauthenticatedAccessToAccount2faRedirectsToLogin(): void
    {
        // Logout the current user by making a request to the logout path.
        $this->client->request( 'GET', '/logout' );

        // Try to access TOTP setup without authentication.
        $crawler = $this->client->request( 'GET', '/admin/account/2fa/setup' );

        // Should redirect to login.
        $this->assertResponseRedirects( '/login', 302 );
    }

    private function generateTotpCode( string $secret ): string
    {
        if ( ! class_exists( 'OTPHP\TOTP' ) ) {
            $this->markTestSkipped( 'otphp package not installed' );
        }

        $totp = \OTPHP\TOTP::create( $secret );
        return $totp->now();
    }

    private function extractCsrfToken( Crawler $crawler ): string
    {
        $csrfInput = $crawler->filter( 'input[name="_csrf_token"]' )->first();

        $this->assertGreaterThan( 0, $csrfInput->count(), 'CSRF token input not found in form' );
        return $csrfInput->attr( 'value' );
    }
}
