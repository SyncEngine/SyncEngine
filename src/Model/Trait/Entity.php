<?php

namespace App\Model\Trait;

use App\Controller\DefaultController;
use App\Repository\Interface\Searchable;
use App\Service\ModelExporter;
use App\Service\ModelNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

trait Entity
{
	protected object $entity;

	public function getEntity(): object
	{
		return $this->entity;
	}

	public function update( EntityManagerInterface $entityManager, $flush = false ): void
	{
		if ( $this->entity->getId() ) {
			$this->persist( $entityManager, $flush );
		}
	}

	public function persist( EntityManagerInterface $entityManager, $flush = false ): void
	{
		// Create ref if not set yet.
		if ( is_callable( [ $this, 'createRef' ] ) ) {
			$this->createRef( false );
		}

		$entityManager->persist( $this->entity );
		if ( $flush ) {
			$entityManager->flush();
		}
	}

	public function normalize(): array
	{
		return ( new ModelNormalizer() )->normalize( $this );
	}

	public function export(): array
	{
		return ( new ModelExporter() )->export( $this );
	}

	public function __call( string $name, array $arguments )
	{
		if ( ! is_callable( [ $this->entity, $name ] ) ) {
			throw new \Exception( 'Method not found: ' . __CLASS__ . '::' . $name );
		}

		return call_user_func_array( [ $this->entity, $name ], $arguments );
	}

	public static function get( $entity ): ?static
	{
		if ( $entity instanceof static ) {
			return $entity;
		}

		if ( empty( static::getEntityClass() ) ) {
			return null;
		}

		if ( is_object( $entity ) && $entity::class === static::getEntityClass() ) {
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
