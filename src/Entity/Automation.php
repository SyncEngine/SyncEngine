<?php

namespace SyncEngine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SyncEngine\Repository\AutomationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity( repositoryClass: AutomationRepository::class )]
#[UniqueEntity( fields: [ 'ref' ], message: 'There is already an automation with this ref, please enter a different ref' )]
#[UniqueEntity( fields: [ 'name' ], message: 'There is already an automation with this name, please enter a different name' )]
#[UniqueEntity( fields: [ 'endpoint' ], message: 'There is already an automation with this endpoint, please enter a different name' )]
class Automation
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

	#[ORM\Column( length: 255, unique: true, nullable: false )]
	private ?string $endpoint = null;

	#[ORM\Column( length: 255, nullable: true )]
	private ?string $supervisor = null;

	#[ORM\Column( nullable: true )]
	private array $config = [];

	#[ORM\Column( nullable: true )]
	private array $data = [];

	#[ORM\OneToMany( mappedBy: 'automation', targetEntity: Trace::class, orphanRemoval: true )]
	private Collection $traces;

	public function __construct()
	{
		$this->traces = new ArrayCollection();
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

	public function getEndpoint(): ?string
	{
		return $this->endpoint;
	}

	public function setEndpoint( ?string $endpoint ): self
	{
		$this->endpoint = $endpoint;

		return $this;
	}

	public function getSupervisor(): ?string
	{
		return $this->supervisor;
	}

	public function setSupervisor( ?string $supervisor ): self
	{
		$this->supervisor = $supervisor;

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

	public function getData(): array
	{
		return $this->data;
	}

	public function setData( array $data ): self
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * @return Collection<int, Trace>
	 */
	public function getTraces(): Collection
	{
		return $this->traces;
	}

	public function addTrace( Trace $trace ): static
	{
		if ( ! $this->traces->contains( $trace ) ) {
			$this->traces->add( $trace );
			$trace->setAutomation( $this );
		}

		return $this;
	}

	public function removeTrace( Trace $trace ): static
	{
		if ( $this->traces->removeElement( $trace ) ) {
			// set the owning side to null (unless already changed)
			if ( $trace->getAutomation() === $this ) {
				$trace->setAutomation( null );
			}
		}

		return $this;
	}
}
