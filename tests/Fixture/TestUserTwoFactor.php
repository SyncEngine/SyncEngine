<?php

namespace SyncEngine\Tests\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SyncEngine\Entity\TwoFactor;
use SyncEngine\Entity\User;

class TestUserTwoFactor
{
	/**
	 * Create a user with TOTP enabled and a known secret for testing.
	 */
	public static function getTotpEnabled( EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher ): User
	{
		$user = TestUser::getOrCreate( $em, $passwordHasher );

		// Only set up TOTP if not already enabled.
		if ( ! $user->isTwoFactorEnabled() || ! $user->isTotpAuthenticationEnabled() ) {
			$secret = bin2hex( random_bytes( 20 ) );

			$twoFactor = new TwoFactor();
			$twoFactor->setUser( $user );
			$twoFactor->setType( 'totp' );
			$twoFactor->setSecret( $secret );
			$twoFactor->setEnabled( true );

			$user->addTwoFactorMethod( $twoFactor );
			$user->setTwoFactorEnabled( true );

			$em->persist( $twoFactor );
		}

		return $user;
	}

	/**
	 * Create a user with email 2FA enabled (no TOTP).
	 */
	public static function getEmailEnabled( EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher ): User
	{
		$user = TestUser::getOrCreate( $em, $passwordHasher );

		// Only set up email 2FA if not already enabled.
		if ( ! $user->isTwoFactorEnabled() ) {
			$twoFactor = new TwoFactor();
			$twoFactor->setUser( $user );
			$twoFactor->setType( 'email' );
			$twoFactor->setEnabled( true );

			$user->addTwoFactorMethod( $twoFactor );
			$user->setTwoFactorEnabled( true );

			$em->persist( $twoFactor );
		}

		return $user;
	}

	/**
	 * Create a user with 2FA disabled (default state).
	 */
	public static function getDisabled( EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher ): User
	{
		$user = TestUser::getOrCreate( $em, $passwordHasher );

		// Ensure 2FA is disabled and no methods exist.
		if ( $user->isTwoFactorEnabled() ) {
			foreach ( $user->getTwoFactorMethods() as $method ) {
				$user->removeTwoFactorMethod( $method );
				$em->remove( $method );
			}

			$user->setTwoFactorEnabled( false );
			$em->flush();
		}

		return $user;
	}

	/**
	 * Get the TOTP secret for a user (if enabled).
	 */
	public static function getTotpSecret( User $user ): ?string
	{
		foreach ( $user->getTwoFactorMethods() as $method ) {
			if ( $method->getType() === 'totp' && $method->isEnabled() ) {
				return $method->getSecret();
			}
		}

		return null;
	}

	/**
	 * Enable TOTP for a user with a specific secret.
	 */
	public static function enableTotpWithSecret( EntityManagerInterface $em, User $user, string $secret ): void
	{
		// Remove any existing TOTP methods.
		foreach ( $user->getTwoFactorMethods() as $method ) {
			if ( $method->getType() === 'totp' ) {
				$user->removeTwoFactorMethod( $method );
				$em->remove( $method );
			}
		}

		// Add new TOTP method.
		$twoFactor = new TwoFactor();
		$twoFactor->setUser( $user );
		$twoFactor->setType( 'totp' );
		$twoFactor->setSecret( $secret );
		$twoFactor->setEnabled( true );

		$user->addTwoFactorMethod( $twoFactor );
		$user->setTwoFactorEnabled( true );

		$em->persist( $twoFactor );
		$em->flush();
	}

	/**
	 * Disable TOTP for a user.
	 */
	public static function disableTotp( EntityManagerInterface $em, User $user ): void
	{
		foreach ( $user->getTwoFactorMethods() as $method ) {
			if ( $method->getType() === 'totp' ) {
				$user->removeTwoFactorMethod( $method );
				$em->remove( $method );
			}
		}

		// Check if any other 2FA methods remain.
		if ( $user->getTwoFactorMethods()->isEmpty() ) {
			$user->setTwoFactorEnabled( false );
		}

		$em->flush();
	}

	public static function generateTotpSecret(): string
	{
		$totp = \OTPHP\TOTP::create();
		return $totp->getSecret();
	}

	public static function generateTotpCode( string $secret ): string
	{
		$totp = \OTPHP\TOTP::create( $secret );
		return $totp->now();
	}
}
