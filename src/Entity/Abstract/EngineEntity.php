<?php

namespace SyncEngine\Entity\Abstract;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use SyncEngine\Attribute\NotExportable;
use SyncEngine\Model\Enum\EntityStatus;
use SyncEngine\Model\Enum\EntityVisibility;

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

	#[ORM\Column( type: 'string', length: 32, options: [ 'default' => EntityStatus::ENABLED->value ] )]
	protected string $status = EntityStatus::ENABLED->value;

	#[ORM\Column( type: 'string', length: 32, options: [ 'default' => EntityVisibility::VISIBLE->value ] )]
	protected string $visibility = EntityVisibility::VISIBLE->value;

	#[ORM\Column]
	protected ?\DateTimeImmutable $created = null;

	#[ORM\Column]
	protected ?\DateTimeImmutable $modified = null;

	#[ORM\Column( length: 255, nullable: true )]
	protected ?string $icon = null;

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

	public function setRef( string $ref ): static
	{
		$this->ref = $ref;

		return $this;
	}

	public function getStatus(): EntityStatus
	{
		return EntityStatus::from( $this->status );
	}

	public function setStatus( EntityStatus $status ): static
	{
		$this->status = $status->value;

		return $this;
	}

	public function getVisibility(): EntityVisibility
	{
		return EntityVisibility::from( $this->visibility );
	}

	public function setVisibility( EntityVisibility $visibility ): static
	{
		$this->visibility = $visibility->value;

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

	public function getIcon(): ?string
	{
		return $this->icon;
	}

	public function setIcon( string $icon ): static
	{
		$this->icon = $icon;

		return $this;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName( string $name ): static
	{
		$this->name = $name;

		return $this;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription( ?string $description ): static
	{
		$this->description = $description;

		return $this;
	}

	public function getSupervisor(): ?string
	{
		return $this->supervisor;
	}

	public function setSupervisor( ?string $supervisor ): static
	{
		$this->supervisor = $supervisor;

		return $this;
	}

	public function getConfig(): array
	{
		return (array) $this->config;
	}

	public function setConfig( array $config ): static
	{
		$this->config = $config;

		return $this;
	}

	public function getData(): array
	{
		return (array) $this->data;
	}

	public function setData( array $data ): static
	{
		$this->data = $data;

		return $this;
	}
}
