<?php

namespace App\Entity;

use App\Repository\AutomationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AutomationRepository::class)]
class Automation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $endpoint = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Connection $sourceConnection = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Connection $targetConnection = null;

    #[ORM\ManyToOne(inversedBy: 'automations')]
    private ?Flow $flow = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSourceConnection(): ?Connection
    {
        return $this->sourceConnection;
    }

    public function setSourceConnection(?Connection $sourceConnection): self
    {
        $this->sourceConnection = $sourceConnection;

        return $this;
    }

    public function getTargetConnection(): ?Connection
    {
        return $this->targetConnection;
    }

    public function setTargetConnection(?Connection $targetConnection): self
    {
        $this->targetConnection = $targetConnection;

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
}
