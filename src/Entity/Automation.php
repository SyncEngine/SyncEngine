<?php

namespace SyncEngine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Ignore;
use SyncEngine\Attribute\NotExportable;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Repository\AutomationRepository;

#[ORM\Entity( repositoryClass: AutomationRepository::class )]
#[UniqueEntity( fields: [ 'endpoint' ], message: 'There is already an automation with this endpoint, please enter a different name' )]
class Automation extends EngineEntity
{
	#[ORM\Column( length: 255, unique: true, nullable: false )]
	private ?string $endpoint = null;

	// @todo Verify if we need `cascade: [ "persist", "remove"]` ?
	#[ORM\OneToMany( mappedBy: 'automation', targetEntity: Trace::class, fetch: "EXTRA_LAZY", orphanRemoval: true )]
	#[ORM\OrderBy(['created' => 'DESC'])]
	#[NotExportable]
	#[Ignore]
	private Collection $traces;

	public function __construct()
	{
		$this->traces = new ArrayCollection();
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
