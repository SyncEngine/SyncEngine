<?php

namespace SyncEngine\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use SyncEngine\Entity\TwoFactor;
use SyncEngine\Entity\User;

class UserTwoFactorTest extends TestCase
{
	private User $user;
	private TwoFactor $twoFactor;

	protected function setUp(): void
	{
		$this->user      = new User();
		$this->twoFactor = new TwoFactor();
	}

	/**
	 * Test that isTwoFactorEnabled returns false by default.
	 */
	public function testTwoFactorEnabledDefaultsToFalse(): void
	{
		$this->assertFalse( $this->user->isTwoFactorEnabled() );
	}

	/**
	 * Test that setTwoFactorEnabled flips the flag correctly.
	 */
	public function testSetTwoFactorEnabledFlipsFlag(): void
	{
		$this->user->setTwoFactorEnabled( true );
		$this->assertTrue( $this->user->isTwoFactorEnabled() );

		$this->user->setTwoFactorEnabled( false );
		$this->assertFalse( $this->user->isTwoFactorEnabled() );
	}

	/**
	 * Test that isTotpAuthenticationEnabled returns false when twoFactorEnabled is false.
	 */
	public function testIsTotpAuthenticationEnabledReturnsFalseWhenDisabled(): void
	{
		$this->assertFalse( $this->user->isTotpAuthenticationEnabled() );
	}

	/**
	 * Test that isTotpAuthenticationEnabled returns false when no TOTP methods exist.
	 */
	public function testIsTotpAuthenticationEnabledReturnsFalseWhenNoMethods(): void
	{
		$this->user->setTwoFactorEnabled( true );
		$this->assertFalse( $this->user->isTotpAuthenticationEnabled() );
	}

	/**
	 * Test that isTotpAuthenticationEnabled returns false when TOTP method is disabled.
	 */
	public function testIsTotpAuthenticationEnabledReturnsFalseWhenMethodDisabled(): void
	{
		$this->user->setTwoFactorEnabled( true );

		$disabledMethod = new TwoFactor();
		$disabledMethod->setType( 'totp' );
		$disabledMethod->setEnabled( false );
		$disabledMethod->setUser( $this->user );

		$this->user->addTwoFactorMethod( $disabledMethod );

		$this->assertFalse( $this->user->isTotpAuthenticationEnabled() );
	}

	/**
	 * Test that isTotpAuthenticationEnabled returns true when enabled TOTP method exists.
	 */
	public function testIsTotpAuthenticationEnabledReturnsTrueWhenMethodEnabled(): void
	{
		$this->user->setTwoFactorEnabled( true );

		$enabledMethod = new TwoFactor();
		$enabledMethod->setType( 'totp' );
		$enabledMethod->setEnabled( true );
		$enabledMethod->setSecret( 'test_secret' );
		$enabledMethod->setUser( $this->user );

		$this->user->addTwoFactorMethod( $enabledMethod );

		$this->assertTrue( $this->user->isTotpAuthenticationEnabled() );
	}

	/**
	 * Test that getTotpAuthenticationConfiguration returns null when no TOTP method exists.
	 */
	public function testGetTotpAuthenticationConfigurationReturnsNullWhenNoMethod(): void
	{
		$this->assertNull( $this->user->getTotpAuthenticationConfiguration() );
	}

	/**
	 * Test that getTotpAuthenticationConfiguration returns valid TotpConfiguration when TOTP method exists.
	 */
	public function testGetTotpAuthenticationConfigurationReturnsValidConfig(): void
	{
		$this->user->setTwoFactorEnabled( true );

		$secret        = 'test_secret_12345';
		$enabledMethod = new TwoFactor();
		$enabledMethod->setType( 'totp' );
		$enabledMethod->setEnabled( true );
		$enabledMethod->setSecret( $secret );
		$enabledMethod->setUser( $this->user );

		$this->user->addTwoFactorMethod( $enabledMethod );

		$config = $this->user->getTotpAuthenticationConfiguration();

		$this->assertNotNull( $config );
		$this->assertInstanceOf( TotpConfiguration::class, $config );

		// Verify the configuration has correct values.
		$this->assertSame( $enabledMethod->getSecret(), $config->getSecret() );
	}

	/**
	 * Test that isEmailAuthEnabled mirrors isTwoFactorEnabled.
	 */
	public function testIsEmailAuthEnabledMirrorsTwoFactorEnabled(): void
	{
		$this->assertFalse( $this->user->isEmailAuthEnabled() );

		$this->user->setTwoFactorEnabled( true );
		$this->assertTrue( $this->user->isEmailAuthEnabled() );

		$this->user->setTwoFactorEnabled( false );
		$this->assertFalse( $this->user->isEmailAuthEnabled() );
	}

	/**
	 * Test that getPreferredTwoFactorProvider returns 'totp' when TOTP is active.
	 */
	public function testGetPreferredTwoFactorProviderReturnsTotpWhenActive(): void
	{
		$this->user->setTwoFactorEnabled( true );

		$enabledMethod = new TwoFactor();
		$enabledMethod->setType( 'totp' );
		$enabledMethod->setEnabled( true );
		$enabledMethod->setUser( $this->user );

		$this->user->addTwoFactorMethod( $enabledMethod );

		$this->assertSame( 'totp', $this->user->getPreferredTwoFactorProvider() );
	}

	/**
	 * Test that getPreferredTwoFactorProvider falls back to 'email' when TOTP is not active.
	 */
	public function testGetPreferredTwoFactorProviderFallsBackToEmail(): void
	{
		$this->user->setTwoFactorEnabled( true );

		// Only email method (no TOTP).
		$emailMethod = new TwoFactor();
		$emailMethod->setType( 'email' );
		$emailMethod->setEnabled( true );
		$emailMethod->setUser( $this->user );

		$this->user->addTwoFactorMethod( $emailMethod );

		$this->assertSame( 'email', $this->user->getPreferredTwoFactorProvider() );
	}

	/**
	 * Test that addTwoFactorMethod adds to collection and sets user reference.
	 */
	public function testAddTwoFactorMethodAddsToCollection(): void
	{
		$this->user->addTwoFactorMethod( $this->twoFactor );

		$methods = $this->user->getTwoFactorMethods();
		$this->assertSame( 1, $methods->count() );
		$this->assertTrue( $methods->contains( $this->twoFactor ) );

		// Verify bidirectional relationship.
		$this->assertSame( $this->user, $this->twoFactor->getUser() );
	}

	/**
	 * Test that removeTwoFactorMethod removes from collection.
	 */
	public function testRemoveTwoFactorMethodRemovesFromCollection(): void
	{
		$this->user->addTwoFactorMethod( $this->twoFactor );
		$this->assertSame( 1, $this->user->getTwoFactorMethods()->count() );

		$this->user->removeTwoFactorMethod( $this->twoFactor );
		$this->assertSame( 0, $this->user->getTwoFactorMethods()->count() );
	}

	/**
	 * Test that TwoFactor constructor sets default timestamps.
	 */
	public function testTwoFactorConstructorSetsTimestamps(): void
	{
		$twoFactor = new TwoFactor();

		$this->assertNotNull( $twoFactor->getCreatedAt() );
		$this->assertNotNull( $twoFactor->getUpdatedAt() );
		$this->assertInstanceOf( \DateTimeImmutable::class, $twoFactor->getCreatedAt() );
		$this->assertInstanceOf( \DateTimeImmutable::class, $twoFactor->getUpdatedAt() );
	}

	/**
	 * Test that TwoFactor defaults enabled to true.
	 */
	public function testTwoFactorDefaultsEnabled(): void
	{
		$twoFactor = new TwoFactor();
		$this->assertTrue( $twoFactor->isEnabled() );
	}

	/**
	 * Test that TwoFactor setType works correctly.
	 */
	public function testTwoFactorSetType(): void
	{
		$twoFactor = new TwoFactor();

		$twoFactor->setType( 'totp' );
		$this->assertSame( 'totp', $twoFactor->getType() );

		$twoFactor->setType( 'email' );
		$this->assertSame( 'email', $twoFactor->getType() );
	}

	/**
	 * Test that TwoFactor setEnabled flips the flag.
	 */
	public function testTwoFactorSetEnabled(): void
	{
		$twoFactor = new TwoFactor();

		$twoFactor->setEnabled( false );
		$this->assertFalse( $twoFactor->isEnabled() );

		$twoFactor->setEnabled( true );
		$this->assertTrue( $twoFactor->isEnabled() );
	}

	/**
	 * Test that TwoFactor setSecret stores value correctly.
	 */
	public function testTwoFactorSetSecret(): void
	{
		$twoFactor = new TwoFactor();
		$secret    = 'test_secret_value';

		$twoFactor->setSecret( $secret );
		$this->assertSame( $secret, $twoFactor->getSecret() );
	}

	/**
	 * Test that TwoFactor setVerifiedAt works correctly.
	 */
	public function testTwoFactorSetVerifiedAt(): void
	{
		$twoFactor = new TwoFactor();

		$this->assertNull( $twoFactor->getVerifiedAt() );

		$verifiedAt = new \DateTimeImmutable( '2024-01-01 00:00:00' );
		$twoFactor->setVerifiedAt( $verifiedAt );

		$this->assertSame( $verifiedAt, $twoFactor->getVerifiedAt() );
	}

	/**
	 * Test that getTotpAuthenticationUsername returns the user's email.
	 */
	public function testGetTotpAuthenticationUsername(): void
	{
		$email = 'test@example.com';
		$this->user->setEmail( $email );

		$this->assertSame( $email, $this->user->getTotpAuthenticationUsername() );
	}

	/**
	 * Test that getEmailAuthRecipient returns the user's email.
	 */
	public function testGetEmailAuthRecipient(): void
	{
		$email = 'test@example.com';
		$this->user->setEmail( $email );

		$this->assertSame( $email, $this->user->getEmailAuthRecipient() );
	}

	/**
	 * Test that setEmailAuthCode stores the code in settings.
	 */
	public function testSetEmailAuthCode(): void
	{
		$code = '123456';

		$this->assertNull( $this->user->getEmailAuthCode() );

		$this->user->setEmailAuthCode( $code );
		$this->assertSame( $code, $this->user->getEmailAuthCode() );
	}
}
