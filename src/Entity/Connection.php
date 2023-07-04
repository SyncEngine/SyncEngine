<?php

namespace App\Entity;

use App\Repository\ConnectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConnectionRepository::class)]
class Connection
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 255, unique: true)]
	private ?string $name = null;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $description = null;

	#[ORM\Column(nullable: true)]
	private array $config = [];

	#[ORM\Column( nullable: true )]
	private array $data = [];

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(?string $description): self
	{
		$this->description = $description;

		return $this;
	}

	public function getConfig(): array
	{
		return $this->config;
	}

	public function setConfig(?array $config): self
	{
		$this->config = $config;

		return $this;
	}

	public function getData(): array {
		return $this->data;
	}

	public function setData( array $data ): self {
		$this->data = $data;

		return $this;
	}
}
