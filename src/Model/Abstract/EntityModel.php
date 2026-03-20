<?php

namespace SyncEngine\Model\Abstract;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Exception\InvalidParameterException;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Repository\Interface\Searchable;
use SyncEngine\Service\ModelNormalizer;

/**
 * @template T of object
 */
abstract class EntityModel extends AbstractModel implements Persistable
{
	/**
	 * @var object<T>
	 */
	protected object $entity;

	/**
	 * @throws \Exception
	 *
	 * @param  object|null  $entity
	 * @psalm-param object<T> $entity
	 */
	public function __construct( ?object $entity = null )
	{
		$class = static::getEntityClass();

		if ( $entity ) {
			if ( ! $entity instanceof $class ) {
				throw new InvalidParameterException( 'Wrong entity type.' );
			}
			$this->entity = $entity;
		}

		parent::__construct();
	}

	public function hasEntity( bool $isPersisted = false ): bool
	{
		if ( $isPersisted ) {
			return ! empty( $this->entity ) && $this->entity->getId();
		}

		return ! empty( $this->entity );
	}

	/**
	 * @return object<T>|null
	 */
	public function getEntity(): ?object
	{
		return $this->entity ?? null;
	}

	public function validate(): bool
	{
		// Create ref if not set yet.
		if ( method_exists( $this, 'createRef' ) ) {
			$this->createRef( false );
		}

		if ( $this instanceof Configurable ) {

			// Refresh config.
			$this->setConfig( $this->entity->getConfig() );

			// Any custom config parser methods.
			if ( method_exists( $this, 'parseConfig' ) ) {
				$this->parseConfig();
			}

			// Create dependency array from config.
			if ( method_exists( $this, 'fetchConfigDependencies' ) ) {
				$this->fetchConfigDependencies();
			}
		}

		if ( $this instanceof Supervisable ) {
			$this->runSupervisorValidate();
		}

		return true;
	}

	/**
	 * Update existing entity.
	 *
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 *
	 * @param  bool  $flush
	 * @param  EntityManagerInterface|null  $entityManager
	 *
	 * @return void
	 */
	public function update( $flush = false, ?EntityManagerInterface $entityManager = null ): void
	{
		if ( $this->entity->getId() ) {
			$this->save( $flush, $entityManager );
		}
	}

	/**
	 * Save and validate entity.
	 *
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 *
	 * @param  bool  $flush
	 * @param  EntityManagerInterface|null  $entityManager
	 *
	 * @return void
	 */
	public function save( $flush = false, ?EntityManagerInterface $entityManager = null ): void
	{
		$success = $this->validate();
		if ( $success ) {
			$this->persist( $flush, $entityManager );
		}
	}

	/**
	 * Update entity without validating and parsing its data.
	 * This should NEVER be used when there is new user input involved!
	 *
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 *
	 * @param  bool  $flush
	 * @param  EntityManagerInterface|null  $entityManager
	 *
	 * @return void
	 */
	public function persist( $flush = false, ?EntityManagerInterface $entityManager = null ): void
	{
		if ( ! $entityManager ) {
			$entityManager = $this->getContainer()->get( 'entitymanager' );
		}

		$entityManager->persist( $this->entity );

		if ( $flush ) {
			$entityManager->flush();
		}
	}

	/**
	 * Remove entity.
	 *
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 *
	 * @param  bool  $flush
	 * @param  EntityManagerInterface|null  $entityManager
	 *
	 * @return bool
	 */
	public function delete( $flush = false, ?EntityManagerInterface $entityManager = null ): bool
	{
		if ( ! $this->hasEntity() ) {
			return false; // @todo Or return true?
		}

		if ( ! $entityManager ) {
			$entityManager = $this->getContainer()->get( 'entitymanager' );
		}

		$entityManager->remove( $this->entity );

		if ( $flush ) {
			$entityManager->flush();
		}

		return true;
	}

	public function normalize( $dependencies = false, $dependents = false ): array
	{
		return $this->getContainer()->get( ModelNormalizer::class )->normalize( $this, $dependencies, $dependents );
	}

	public function __call( string $name, array $arguments )
	{
		if ( ! isset( $this->entity ) || ! is_callable( [ $this->entity, $name ] ) ) {
			throw new \ErrorException( 'Method not found: ' . __CLASS__ . '::' . $name );
		}

		return call_user_func_array( [ $this->entity, $name ], $arguments );
	}

	/**
	 * @throws \Exception
	 *
	 * @param $entity
	 * @psalm-param object<T> $entity
	 *
	 * @return static
	 */
	public static function create( $entity = null ): static
	{
		if ( ! $entity ) {
			$class  = static::getEntityClass();
			$entity = new $class();
		}

		return new static( $entity );
	}

	public static function get( $entity, $type = null ): ?static
	{
		if ( $entity instanceof static ) {
			return $entity;
		}

		if ( ! $entity ) {
			return null;
		}

		if ( $type ) {
			$model = static::getEntityModelClass( ucfirst( $type ) );
			if ( class_exists( $model ) ) {
				// @todo is the iterable check even needed?
				return $model::get( is_iterable( $entity ) ? $entity['id'] ?? $entity['ref'] ?? $entity : $entity );
			}
		}

		if ( is_object( $entity ) && self::getEntityRealClass( $entity ) === static::getEntityClass() ) {
			return static::create( $entity );
		}

		$repository = static::getRepository();

		if ( is_numeric( $entity ) ) {
			$entity = $repository->findOneBy( [ 'id' => $entity ] );
		} elseif ( is_string( $entity ) ) {
			$entity = $repository->findOneBy( [ 'ref' => $entity ] );
		} else {
			// Only keep valid columns for get query. Use getAll or repository for search etc.
			$entity = array_intersect_key( $entity, array_flip( [ 'id', 'ref', 'endpoint' ] ) );
			$entity = $repository->findOneBy( $entity );
		}

		return ( $entity ) ? static::create( $entity ) : null;
	}

	/**
	 * @throws \Exception
	 *
	 * @param  array  $query
	 *
	 * @return static[]
	 */
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
			$models[] = static::create( $entity );
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

	public static function getRepository( ?EntityManagerInterface $entityManager = null ): EntityRepository
	{
		if ( ! $entityManager ) {
			$entityManager = DefaultController::getEntityManager();
		}

		return $entityManager->getRepository( static::getEntityClass() );
	}

	public static function getEntityModelClass( $entity ): ?string
	{
		if ( is_object( $entity ) ) {
			$entity = self::getEntityReflection( $entity )->getShortName();
		}

		return static::getModelClass( $entity );
	}

	public static function getEntityReflection( $entity ): \ReflectionClass
	{
		if ( is_object( $entity ) ) {
			$entity = get_class( $entity );
		}

		return \Doctrine\Common\Util\ClassUtils::newReflectionClass( $entity );
	}

	public static function getEntityRealClass( $entity ): string
	{
		if ( is_object( $entity ) ) {
			$entity = get_class( $entity );
		}

		return \Doctrine\Common\Util\ClassUtils::getRealClass( $entity );
	}

	/**
	 * @return string
	 * @psalm-return class-string<T>
	 */
	abstract public static function getEntityClass(): string;
}
