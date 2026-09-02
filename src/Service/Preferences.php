<?php

namespace SyncEngine\Service;

use Symfony\Bundle\SecurityBundle\Security;
use SyncEngine\Entity\User;
use SyncEngine\Repository\UserRepository;
use SyncEngine\Service\Interface\SettingsInterface;

class Preferences implements SettingsInterface
{
	public function __construct(
		private readonly Security $security,
		private readonly UserRepository $repository,
	) {}

	private function getUser(): ?User
	{
		/** @var ?User */
		return $this->security->getUser();
	}

	public function exists(): bool
	{
		return ! empty( $this->getUser()?->getUserIdentifier() );
	}

	public function get( string $key ): mixed
	{
		return $this->getUser()->getSetting( $key );
	}

	public function set( string $key, mixed $value ): static
	{
		$this->validateKey( $key );
		$this->validateValue( $value );

		$this->getUser()->setSetting( $key, $value );

		return $this;
	}

	private function validateKey( string $key ): void
	{
		// Allow ``, `-`, `.`, `\`, '/' and `:` in addition to alphanumeric characters and underscores
		if ( preg_match( '/[^a-zA-Z0-9_\/\-\.\\\:]/', $key ) ) {
			throw new \InvalidArgumentException( 'Preference key contains invalid characters' );
		}

		if ( strlen( $key ) > 255 ) {
			throw new \InvalidArgumentException( 'Preference key exceeds maximum length of 255 characters' );
		}
	}

	private function validateValue( mixed $value ): void
	{
		if ( ! $this->isScalarOrPlainArray( $value ) ) {
			throw new \InvalidArgumentException( 'Preference value must be scalar or a plain array of scalars' );
		}
	}

	private function isScalarOrPlainArray( mixed $value ): bool
	{
		if ( is_scalar( $value ) || null === $value ) {
			return true;
		}

		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $item ) {
			if ( ! $this->isScalarOrPlainArray( $item ) ) {
				return false;
			}
		}

		return true;
	}

	public function unset( string $key ): static
	{
		$this->getUser()->unsetSetting( $key );

		return $this;
	}

	public function fetch(): ?array
	{
		return $this->getUser()->getSettings();
	}

	public function update( string $key, mixed $value ): bool
	{
		$this->set( $key, $value );
		return $this->persist();
	}

	public function persist(): bool
	{
		$this->repository->save( $this->getUser(), true );

		return true;
	}
}
