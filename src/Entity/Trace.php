<?php

namespace SyncEngine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;
use SyncEngine\Repository\TraceRepository;

#[ORM\Entity( repositoryClass: TraceRepository::class )]
//#[Broadcast]
class Trace
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\ManyToOne( inversedBy: 'traces' )]
	#[ORM\JoinColumn( nullable: false )]
	private ?Automation $automation = null;

	#[ORM\Column]
	private ?\DateTimeImmutable $createdAt = null;

	#[ORM\Column]
	private ?\DateTimeImmutable $updatedAt = null;

	#[ORM\Column( length: 255 )]
	private ?string $status = null;

	#[ORM\Column]
	private array $data = [];

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId( int $id ): static
	{
		$this->id = $id;

		return $this;
	}

	public function getAutomation(): ?Automation
	{
		return $this->automation;
	}

	public function setAutomation( ?Automation $automation ): static
	{
		$this->automation = $automation;

		return $this;
	}

	public function getCreatedAt(): ?\DateTimeImmutable
	{
		return $this->createdAt;
	}

	public function setCreatedAt( \DateTimeImmutable $createdAt ): static
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	public function getUpdatedAt(): ?\DateTimeImmutable
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt( \DateTimeImmutable $updatedAt ): static
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

	public function getStatus(): ?string
	{
		return $this->status;
	}

	public function setStatus( string $status ): static
	{
		$this->status = $status;

		return $this;
	}

	public function getData(): array
	{
		return $this->data;
	}

	public function setData( array $data ): static
	{
		$this->data = $data;

		return $this;
	}
}
