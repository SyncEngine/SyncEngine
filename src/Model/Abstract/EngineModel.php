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
 * @method int|null getId()
 * @method setId( int $id )
 * @method string|null getRef()
 * @method setRef( string $ref )
 * @method string getName()
 * @method setName( string $name )
 * @method string|null getDescription()
 * @method setDescription( string $description )
 * @method \DateTimeImmutable|null getCreated()
 * @method setCreated( \DateTimeImmutable $created )
 * @method \DateTimeImmutable|null getModified()
 * @method setModified( \DateTimeImmutable $modified )
 * @method string|null getIcon()
 * @method setIcon( string $icon )
 * @method string|null getSupervisor()
 * @method setSupervisor( string $supervisor )
 * @method array getConfig()
 * @method setConfig( array $config )
 * @method array getData()
 * @method setData( array $data )
 */
abstract class EngineModel extends EntityModel implements Exportable, Configurable
{
	use Ref;
	use Config;
	use Data;

	/**
	 * @inheritDoc
	 * @param  EngineEntity|null  $entity
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
