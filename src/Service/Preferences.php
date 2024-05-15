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
		/** @var User */
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
		$this->getUser()->setSetting( $key, $value );

		return $this;
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
