<?php

namespace SyncEngine\Model\Abstract;

use Doctrine\ORM\EntityManagerInterface;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Exportable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Model\Trait\Data;
use SyncEngine\Model\Trait\Ref;
use SyncEngine\Service\ModelExporter;

/**
 * @template T of EngineEntity
 *
 * @extends EntityModel<T>
 *
 * @method null|int getId()
 * @method $this setId( int $id )
 * @method null|string getRef()
 * @method $this setRef( string $ref )
 * @method null|string getName()
 * @method $this setName( string $name )
 * @method null|string getDescription()
 * @method $this setDescription( ?string $description )
 * @method null|\DateTimeImmutable getCreated()
 * @method $this setCreated( \DateTimeImmutable $created )
 * @method null|\DateTimeImmutable getModified()
 * @method $this setModified( \DateTimeImmutable $modified )
 * @method null|string getIcon()
 * @method $this setIcon( string $icon )
 * @method null|string getSupervisor()
 * @method $this setSupervisor( ?string $supervisor )
 */
abstract class EngineModel extends EntityModel implements Exportable, Configurable
{
	use Ref;
	use Config;
	use Data;

	/**
	 * @inheritDoc
	 * @param  T|null  $entity
	 */
	public function __construct( ?EngineEntity $entity = null )
	{
		parent::__construct( $entity );
	}

	public function export(): array
	{
		// Run validation to trigger parsers and supervisors.
		$this->validate();

		return $this->getContainer()->get( ModelExporter::class )->export( $this );
	}

	public function update( $flush = false, ?EntityManagerInterface $entityManager = null ): void
	{
		if ( $this->hasEntity() ) {
			$this->getEntity()->setModified( new \DateTimeImmutable() );
		}

		parent::update( $flush, $entityManager );
	}

	public function persist( $flush = false, ?EntityManagerInterface $entityManager = null ): void
	{
		$entity = $this->getEntity();

		if ( ! $entity->getCreated() ) {
			$entity->setCreated( new \DateTimeImmutable() );
		}
		if ( ! $entity->getModified() ) {
			$entity->setModified( new \DateTimeImmutable() );
		}

		parent::persist( $flush, $entityManager );
	}
}
