<?php

namespace SyncEngine\Entity;

use Doctrine\ORM\Mapping as ORM;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Repository\DatasetRepository;

#[ORM\Entity( repositoryClass: DatasetRepository::class )]
class Dataset extends EngineEntity
{
	#[ORM\Column( length: 255 )]
	private ?string $type = null;

	#[ORM\Column( nullable: true )]
	private ?array $data = [];

	public function getType(): ?string
	{
		return $this->type;
	}

	public function setType( string $type ): self
	{
		$this->type = $type;

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
