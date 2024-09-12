<?php

namespace SyncEngine\Entity\Abstract;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use SyncEngine\Attribute\NotExportable;

#[UniqueEntity( fields: [ 'ref' ], message: 'There is already an automation with this ref, please enter a different ref' )]
#[UniqueEntity( fields: [ 'name' ], message: 'There is already an automation with this name, please enter a different name' )]
class EngineEntity
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	protected ?int $id = null;

	#[ORM\Column( length: 255, unique: true )]
	protected ?string $ref = null;

	#[ORM\Column]
	protected ?\DateTimeImmutable $created = null;

	#[ORM\Column]
	protected ?\DateTimeImmutable $modified = null;

	#[ORM\Column( length: 255, unique: true )]
	protected ?string $name = null;

	#[ORM\Column( length: 255, nullable: true )]
	protected ?string $description = null;

	#[ORM\Column( length: 255, nullable: true )]
	protected ?string $supervisor = null;

	#[ORM\Column( nullable: true )]
	protected ?array $config = [];

	#[ORM\Column( nullable: true )]
	#[NotExportable]
	protected ?array $data = [];

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getRef(): ?string
	{
		return $this->ref;
	}

	public function setRef( string $ref ): self
	{
		$this->ref = $ref;

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

	public function getModified(): ?\DateTimeImmutable
	{
		return $this->modified;
	}

	public function setModified( \DateTimeImmutable $modified ): static
	{
		$this->modified = $modified;

		return $this;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName( string $name ): self
	{
		$this->name = $name;

		return $this;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription( ?string $description ): self
	{
		$this->description = $description;

		return $this;
	}

	public function getSupervisor(): ?string
	{
		return $this->supervisor;
	}

	public function setSupervisor( ?string $supervisor ): self
	{
		$this->supervisor = $supervisor;

		return $this;
	}

	public function getConfig(): array
	{
		return (array) $this->config;
	}

	public function setConfig( array $config ): self
	{
		$this->config = $config;

		return $this;
	}

	public function getData(): array
	{
		return (array) $this->data;
	}

	public function setData( array $data ): self
	{
		$this->data = $data;

		return $this;
	}
}
