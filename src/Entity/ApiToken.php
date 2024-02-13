<?php

namespace SyncEngine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;
use SyncEngine\Repository\ApiTokenRepository;

#[ORM\Entity( repositoryClass: ApiTokenRepository::class )]
#[Broadcast]
class ApiToken
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column( length: 255 )]
	private ?string $token = null;

	#[ORM\ManyToOne( inversedBy: 'apiTokens' )]
	#[ORM\JoinColumn( nullable: false )]
	private ?User $user = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getToken(): ?string
	{
		return $this->token;
	}

	public function setToken( string $token ): static
	{
		$this->token = $token;

		return $this;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser( ?User $user ): static
	{
		$this->user = $user;

		return $this;
	}
}
