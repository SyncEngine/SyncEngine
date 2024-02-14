<?php

namespace SyncEngine\Entity;

use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expires = null;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getExpires(): ?\DateTimeInterface
    {
        return $this->expires;
    }

    public function setExpires(?\DateTimeInterface $expires): static
    {
        $this->expires = $expires;

        return $this;
    }
}
