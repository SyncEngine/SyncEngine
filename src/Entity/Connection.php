<?php

namespace SyncEngine\Entity;

use Doctrine\ORM\Mapping as ORM;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Repository\ConnectionRepository;

#[ORM\Entity( repositoryClass: ConnectionRepository::class )]
class Connection extends EngineEntity
{
	#[ORM\Column( nullable: true )]
	private ?array $data = [];

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
