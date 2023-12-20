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
	private ?automation $automation = null;

	#[ORM\Column]
	private ?\DateTimeImmutable $created = null;

	#[ORM\Column( length: 255 )]
	private ?string $status = null;

	#[ORM\Column]
	private array $trace = [];

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId( int $id ): static
	{
		$this->id = $id;

		return $this;
	}

	public function getAutomation(): ?automation
	{
		return $this->automation;
	}

	public function setAutomation( ?automation $automation ): static
	{
		$this->automation = $automation;

		return $this;
	}

	public function getCreated(): ?\DateTimeImmutable
	{
		return $this->created;
	}

	public function setCreated( \DateTimeImmutable $created ): static
	{
		$this->created = $created;

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

	public function getTrace(): array
	{
		return $this->trace;
	}

	public function setTrace( array $trace ): static
	{
		$this->trace = $trace;

		return $this;
	}
}
