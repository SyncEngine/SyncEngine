<?php

namespace App\Entity;

use App\Repository\ConnectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity( repositoryClass: ConnectionRepository::class )]
class Connection
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

	#[ORM\Column( nullable: true )]
	private array $config = [];

	#[ORM\Column( nullable: true )]
	private array $data = [];

	#[ORM\OneToMany( mappedBy: 'connection', targetEntity: Relation::class, cascade: ['persist'], orphanRemoval: true )]
	private Collection $relations;

	public function __construct()
	{
		$this->relations = new ArrayCollection();
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

	public function getConfig(): array
	{
		return $this->config;
	}

	public function setConfig( ?array $config ): self
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
			$relation->setConnection( $this );
		}

		return $this;
	}

	public function removeRelation( Relation $relation ): static
	{
		if ( $this->relations->removeElement( $relation ) ) {
			// set the owning side to null (unless already changed)
			if ( $relation->getConnection() === $this ) {
				$relation->setConnection( null );
			}
		}

		return $this;
	}
}
