<?php

namespace SyncEngine\Entity;

use Doctrine\ORM\Mapping as ORM;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Repository\StorageRepository;

#[ORM\Entity( repositoryClass: StorageRepository::class )]
class Storage extends EngineEntity
{
	#[ORM\Column( length: 255 )]
	private ?string $type = null;

	public function getType(): ?string
	{
		return $this->type;
	}

	public function setType( string $type ): self
	{
		$this->type = $type;

		return $this;
	}
}
