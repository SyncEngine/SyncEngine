<?php

namespace App\Entity;

use App\Repository\FlowRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity( repositoryClass: FlowRepository::class )]
class Flow
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column( length: 255, unique: true )]
	private ?string $ref = null;

	#[ORM\Column( length: 255, unique: true )]
	private ?string $name = null;

	#[ORM\Column( length: 255, nullable: true )]
	private ?string $description = null;

	#[ORM\OneToMany( mappedBy: 'flow', targetEntity: Automation::class )]
	private Collection $automations;

	#[ORM\Column( nullable: true )]
	private array $steps = [];

	#[ORM\Column( nullable: true )]
	private array $config = [];

	#[ORM\OneToMany( mappedBy: 'flow', targetEntity: Relation::class )]
	private Collection $relations;

	public function __construct()
	{
		$this->automations = new ArrayCollection();
		$this->relations   = new ArrayCollection();
	}

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

	/**
	 * @return Collection<int, Automation>
	 */
	public function getAutomations(): Collection
	{
		return $this->automations;
	}

	public function addAutomation( Automation $automation ): self
	{
		if ( ! $this->automations->contains( $automation ) ) {
			$this->automations->add( $automation );
			$automation->setFlow( $this );
		}

		return $this;
	}

	public function removeAutomation( Automation $automation ): self
	{
		if ( $this->automations->removeElement( $automation ) ) {
			// set the owning side to null (unless already changed)
			if ( $automation->getFlow() === $this ) {
				$automation->setFlow( null );
			}
		}

		return $this;
	}

	public function getSteps(): array
	{
		return $this->steps;
	}

	public function setSteps( ?array $steps ): self
	{
		$this->steps = $steps;

		return $this;
	}

	public function getConfig(): array
	{
		return $this->config;
	}

	public function setConfig( array $config ): self
	{
		$this->config = $config;

		return $this;
	}

	/**
	 * @return Collection<int, Relation>
	 */
	public function getRelations(): Collection
	{
		return $this->relations;
	}

	public function addRelation( Relation $relation ): static
	{
		if ( ! $this->relations->contains( $relation ) ) {
			$this->relations->add( $relation );
			$relation->setFlow( $this );
		}

		return $this;
	}

	public function removeRelation( Relation $relation ): static
	{
		if ( $this->relations->removeElement( $relation ) ) {
			// set the owning side to null (unless already changed)
			if ( $relation->getFlow() === $this ) {
				$relation->setFlow( null );
			}
		}

		return $this;
	}
}
