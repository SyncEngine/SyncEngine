<?php

namespace App\Entity;

use App\Repository\AutomationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AutomationRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'There is already an automation with this slug, please enter a different name')]
class Automation
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 255)]
	private ?string $ref = null;

	#[ORM\Column(length: 255)]
	private ?string $name = null;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $description = null;

	#[ORM\Column(length: 255, nullable: false, unique: true)]
	private ?string $endpoint = null;

	#[ORM\ManyToOne(inversedBy: 'automations')]
	private ?Flow $flow = null;

	#[ORM\Column( nullable: true )]
	private array $config = [];

	#[ORM\Column( nullable: true )]
	private array $data = [];


	public function getId(): ?int
	{
		return $this->id;
	}

	public function getRef(): ?string
	{
		return $this->ref;
	}

	public function setRef(string $ref): self
	{
		$this->ref = $ref;

		return $this;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
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

	public function getEndpoint(): ?string
	{
		return $this->endpoint;
	}

	public function setEndpoint(?string $endpoint): self
	{
		$this->endpoint = $endpoint;

		return $this;
	}

	public function getFlow(): ?Flow
	{
		return $this->flow;
	}

	public function setFlow(?Flow $flow): self
	{
		$this->flow = $flow;

		return $this;
	}

	public function getConfig(): array {
		return $this->config;
	}

	public function setConfig( array $config ): self {
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
