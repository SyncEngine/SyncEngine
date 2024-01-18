<?php

namespace SyncEngine\Model\Abstract;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SyncEngine\Controller\Abstract\EntityController;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Repository\Interface\Searchable;

abstract class EntityModel extends AbstractModel implements Persistable
{
	protected object $entity;

	public function __construct( object $entity = null )
	{
		$class = static::getEntityClass();

		if ( $entity ) {
			if ( ! $entity instanceof $class ) {
				throw new \Exception( 'Wrong entity type.' );
			}
			$this->entity = $entity;
		}

		parent::__construct();
	}

	public function hasEntity(): bool
	{
		return ! empty( $this->entity );
	}

	public function getEntity(): object
	{
		return $this->entity;
	}

	public function update( $flush = false, ?EntityManagerInterface $entityManager = null ): void
	{
		if ( $this->entity->getId() ) {
			$this->persist( $flush, $entityManager );
		}
	}

	public function save(): void
	{
		// Create ref if not set yet.
		if ( method_exists( $this, 'createRef' ) ) {
			$this->createRef( false );
		}

		if ( $this instanceof Configurable ) {

			// Refresh config.
			$this->setConfig( $this->entity->getConfig() );

			// Create dependency array from config.
			if ( method_exists( $this, 'fetchConfigDependencies' ) ) {
				$this->fetchConfigDependencies();
			}

			// Any custom config parser methods.
			if ( method_exists( $this, 'parseConfig' ) ) {
				$this->parseConfig();
			}
		}
	}

	public function persist( $flush = false, ?EntityManagerInterface $entityManager = null ): void
	{
		$this->save();

		if ( ! $entityManager ) {
			$entityManager = $this->getContainer()->get( 'entitymanager' );
		}

		$entityManager->persist( $this->entity );
		if ( $flush ) {
			$entityManager->flush();
		}
	}

	public function normalize( $dependencies = false, $dependents = false ): array
	{
		return $this->getContainer()->get( 'ModelNormalizer' )->normalize( $this, $dependencies, $dependents );
	}

	public function __call( string $name, array $arguments )
	{
		if ( ! is_callable( [ $this->entity, $name ] ) ) {
			throw new \Exception( 'Method not found: ' . __CLASS__ . '::' . $name );
		}

		return call_user_func_array( [ $this->entity, $name ], $arguments );
	}

	public static function create( $entity = null ): static
	{
		$class = static::getEntityClass();

		if ( ! $entity ) {
			$entity = new $class;
		}

		return new static( $entity );
	}

	public static function get( $entity ): ?static
	{
		if ( $entity instanceof static ) {
			return $entity;
		}

		if ( ! $entity || empty( static::getEntityClass() ) ) {
			return null;
		}

		if ( is_object( $entity ) && EntityController::getEntityClass( $entity ) === static::getEntityClass() ) {
			return new static( $entity );
		}

		$repository = static::getRepository();

		if ( is_numeric( $entity ) ) {
			$entity = $repository->findOneBy( [ 'id' => $entity ] );
		} elseif ( is_string( $entity ) ) {
			$entity = $repository->findOneBy( [ 'ref' => $entity ] );
		} else {
			$entity = $repository->findOneBy( $entity );
		}

		return ( $entity ) ? new static( $entity ) : null;
	}

	public static function getAll( array $query = [] ): array
	{
		if ( empty( static::getEntityClass() ) ) {
			return [];
		}

		$repository = static::getRepository();

		if ( $query ) {
			$order  = $query['orderBy'] ?? $query['order'] ?? $query['sort'] ?? null;
			$limit  = $query['limit'] ?? $query['max'] ?? null;
			$offset = $query['offset'] ?? null;
			$where  = $query['where'] ?? [];

			if ( ! empty( $query['search'] ) && $repository instanceof Searchable ) {
				$search = $query['search'];
				if ( ! is_array( $search ) ) {
					$search = array( 'name' => $search );
				}
				$entities = $repository->searchBy( $search, $where, $order, $limit, $offset );
			} else {
				$entities = $repository->findBy( $where, $order, $limit, $offset );
			}
		} else {
			$entities = $repository->findAll();
		}

		$models = [];
		foreach ( $entities as $entity ) {
			$models[] = new static( $entity );
		}

		return $models;
	}

	public static function getTotalCount( array $query = [] ): int
	{
		if ( empty( static::getEntityClass() ) ) {
			return 0;
		}

		$repository = static::getRepository();
		return $repository->count( $query['where'] ?? [] );
	}

	public static function getRepository( EntityManagerInterface $entityManager = null ): EntityRepository
	{
		if ( ! $entityManager ) {
			$entityManager = DefaultController::getEntityManager();
		}

		return $entityManager->getRepository( static::getEntityClass() );
	}

	abstract public static function getEntityClass();
}
