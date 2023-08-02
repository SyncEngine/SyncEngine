<?php

namespace App\Entity;

use App\Repository\RelationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: RelationRepository::class)]
class Relation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'relations')]
    private ?Automation $Automation = null;

    #[ORM\ManyToOne(inversedBy: 'relations')]
    private ?Flow $flow = null;

    #[ORM\ManyToOne(inversedBy: 'relations')]
    private ?Step $step = null;

    #[ORM\ManyToOne(inversedBy: 'relations')]
    private ?Connection $connection = null;

    #[ORM\ManyToOne(inversedBy: 'relations')]
    private ?Dataset $dataset = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAutomation(): ?Automation
    {
        return $this->Automation;
    }

    public function setAutomation(?Automation $Automation): static
    {
        $this->Automation = $Automation;

        return $this;
    }

    public function getFlow(): ?Flow
    {
        return $this->flow;
    }

    public function setFlow(?Flow $flow): static
    {
        $this->flow = $flow;

        return $this;
    }

    public function getStep(): ?Step
    {
        return $this->step;
    }

    public function setStep(?Step $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function getConnection(): ?Connection
    {
        return $this->connection;
    }

    public function setConnection(?Connection $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    public function getDataset(): ?Dataset
    {
        return $this->dataset;
    }

    public function setDataset(?Dataset $dataset): static
    {
        $this->dataset = $dataset;

        return $this;
    }
}
