<?php

namespace App\Entity;

use App\Repository\StepRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StepRepository::class)]
class Step
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 255)]
	private ?string $name = null;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $description = null;

	#[ORM\Column(nullable: true)]
	private array $config = [];

	#[ORM\OneToMany(mappedBy: 'step', targetEntity: StepOrder::class, orphanRemoval: true)]
	private Collection $stepOrders;

	public function __construct()
	{
		$this->stepOrders = new ArrayCollection();
	}

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

	public function getConfig(): array
	{
		return $this->config;
	}

	public function setConfig(?array $config): self
	{
		$this->config = $config;

		return $this;
	}

	/**
	 * @return Collection<int, StepOrder>
	 */
	public function getStepOrders(): Collection
	{
		return $this->stepOrders;
	}

	public function addStepOrder(StepOrder $stepOrder): self
	{
		if (!$this->stepOrders->contains($stepOrder)) {
			$this->stepOrders->add($stepOrder);
			$stepOrder->setStep($this);
		}

		return $this;
	}

	public function removeStepOrder(StepOrder $stepOrder): self
	{
		if ($this->stepOrders->removeElement($stepOrder)) {
			// set the owning side to null (unless already changed)
			if ($stepOrder->getStep() === $this) {
				$stepOrder->setStep(null);
			}
		}

		return $this;
	}
}
