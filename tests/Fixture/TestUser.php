<?php

namespace SyncEngine\Tests\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SyncEngine\Entity\User;

class TestUser
{
	public const EMAIL = 'test@example.com';
	public const PASSWORD = 'testpassword123';
	public const NAME = 'Test User';

	public static function create( EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, ?array $roles = null ): User
	{
		$user = new User();
		$user->setEmail( self::EMAIL );
		$user->setName( self::NAME );
		$user->setPassword( $passwordHasher->hashPassword( $user, self::PASSWORD ) );
		$user->setRoles( $roles ?: [ 'ROLE_ADMIN' ] );

		$em->persist( $user );
		$em->flush();

		return $user;
	}

	public static function getOrCreate( EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, ?array $roles = [] ): User
	{
		/** @var \SyncEngine\Repository\UserRepository $repo */
		$repo = $em->getRepository( User::class );
		$user = $repo->findOneBy( [ 'email' => self::EMAIL ] );

		if ( $user ) {
			if ( array_diff( $roles, $user->getRoles() ) ) {
				$user->setRoles( $roles );
				$em->persist( $user );
				$em->flush();
			}

			return $user;
		}

		return self::create( $em, $passwordHasher, $roles );
	}
}
