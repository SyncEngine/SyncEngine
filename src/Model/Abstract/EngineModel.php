<?php

namespace SyncEngine\Model\Abstract;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Exception\NotAllowedException;
use SyncEngine\Model\Enum\EntityStatus;
use SyncEngine\Model\Enum\EntityVisibility;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Exportable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Model\Trait\Data;
use SyncEngine\Model\Trait\Ref;
use SyncEngine\Service\ModelDependencyManager;
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
 * @method EntityStatus|null getStatus()
 * @method $this setStatus( EntityStatus $status )
 * @method EntityVisibility|null getVisibility()
 * @method $this setVisibility( EntityVisibility $visibility )
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

	public function delete( $flush = false, ?EntityManagerInterface $entityManager = null ): bool
	{
		if ( ! $this->hasEntity() ) {
			return false; // @todo Or return true?
		}

		$manager = $this->getContainer()->get( ModelDependencyManager::class ) ?? new ModelDependencyManager();
		if ( $manager->hasDependents( $this ) ) {
			throw new NotAllowedException( 'Cannot delete entity with dependents.' );
		}

		return parent::delete( $flush, $entityManager );
	}

	public function trash( $flush = false, ?EntityManagerInterface $entityManager = null ): bool
	{
		if ( ! $this->hasEntity() ) {
			return false; // @todo Or return true?
		}

		// Check dependents (runtime impact)
		$manager = $this->getContainer()->get( ModelDependencyManager::class ) ?? new ModelDependencyManager();
		if ( $manager->hasDependents( $this ) ) {
			throw new NotAllowedException( 'Cannot trash entity with dependents.' );
		}

		$this->setStatus( EntityStatus::TRASHED );
		$this->setConfig( time(), '_trashedAt' );
		$this->persist( $flush, $entityManager );

		return true;
	}

	public function restore( $flush = false, ?EntityManagerInterface $entityManager = null ): bool
	{
		if ( ! $this->isTrashed() ) {
			return false;
		}

		$this->setStatus( EntityStatus::ENABLED );
		$this->setConfig( null, '_trashedAt' );
		$this->persist( $flush, $entityManager );

		return true;
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

	public function getTrashedAt(): ?\DateTimeImmutable
	{
		$trashedAt = $this->getConfig( '_trashedAt' );

		if ( $trashedAt ) {
			return \DateTimeImmutable::createFromFormat( 'U', $trashedAt );
		}

		return null;
	}

	public function isEnabled(): bool
	{
		return EntityStatus::ENABLED === $this->getStatus();
	}

	public function isDisabled(): bool
	{
		return EntityStatus::DISABLED === $this->getStatus();
	}

	public function isTrashed(): bool
	{
		return EntityStatus::TRASHED === $this->getStatus();
	}

	public function isVisible(): bool
	{
		return EntityVisibility::VISIBLE === $this->getVisibility();
	}

	public function isHidden(): bool
	{
		return EntityVisibility::HIDDEN === $this->getVisibility();
	}

	public function handleRequest( Request $request ): Response
	{
		return new Response( '', Response::HTTP_NOT_IMPLEMENTED );
	}

	public static function getAll( array $query = [] ): array
	{
		if ( $query ) {
			$query = self::parseQuery( $query );
		}

		return parent::getAll( $query ); // TODO: Change the autogenerated stub
	}

	public static function getTotalCount( array $query = [] ): int
	{
		if ( $query ) {
			$query = self::parseQuery( $query );
		}

		return parent::getTotalCount( $query ); // TODO: Change the autogenerated stub
	}

	public static function parseQuery( array $query ): array
	{
		// Apply defaults ONLY if not explicitly set in where
		if ( ! isset( $query['where']['status'] ) ) {
			$query['where']['status'] = EntityStatus::ENABLED->value;
		}

		if ( ! isset( $query['where']['visibility'] ) ) {
			$query['where']['visibility'] = EntityVisibility::VISIBLE->value;
		}

		return $query;
	}
}
