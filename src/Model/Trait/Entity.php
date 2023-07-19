<?php

namespace App\Model\Trait;

use App\Controller\DefaultController;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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
		$defaultContext = [
			AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ( object $object ): string {
				return $object->getId();
			},
		];

		$normalizer = new ObjectNormalizer( null, null, null, null, null, null, $defaultContext );

		return $normalizer->normalize( $this->entity );
	}

	public function export(): array
	{
		static $dependencies = [];

		$defaultContext = [
			AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ( object $object ): string {
				return $object->getId();
			},
		];
		$normalizer = new ObjectNormalizer( null, null, null, null, null, null, $defaultContext );

		$propertyAccess = new PropertyAccessor();

		$key = ( is_callable( [ $this, 'getRef' ] ) ) ? $this->getRef() : '_';
		$export = [ $key => [] ];
		$classRef = new \ReflectionClass( $this->entity );

		foreach ( $classRef->getProperties() as $property ) {
			$getter = 'get' . ucfirst( $property->getName() );
			if ( is_callable( $this->entity, $getter ) ) {
				// Call Model method instead of entity to allow context overrides.
				$value = call_user_func( [ $this, $getter ] );
			} else {
				$value = $propertyAccess->getValue( $this->entity, $property->getName() );
			}
			if ( $value && is_object( $value ) ) {
				if ( is_iterable( $value ) ) {
					$iterable = $value;
					$value = [];
					foreach ( $iterable as $relKey => $relation ) {
						$model = '\\App\\Model\\' . ( new \ReflectionClass( $relation ) )->getShortName() . 'Model';

						if ( is_callable( [ $relation, 'getRef' ] ) && class_exists( $model ) ) {
							if ( ! isset( $dependencies[ $relation->getRef() ] ) ) {
								$dependencies[ $relation->getRef() ] = new $model( $relation );
							}
							$relation = $relation->getRef();
						} else {
							$relation = $normalizer->normalize( $relation );
						}
						$value[ $relKey ] = $relation;
					}
				} else {
					$model = '\\App\\Model\\' . ( new \ReflectionClass( $value ) )->getShortName() . 'Model';
					if ( is_callable( [ $value, 'getRef' ] ) && class_exists( $model ) ) {
						if ( ! isset( $dependencies[ $value->getRef() ] ) ) {
							$dependencies[ $value->getRef() ] = new $model( $value );
						}
						$value = $value->getRef();
					} else {
						$value = $normalizer->normalize( $value );
					}
				}
			}
			$export[ $key ][ $property->name ] = $value;
		}

		foreach ( $dependencies as $ref => $dependency ) {
			if ( ! is_object( $dependency ) ) {
				continue;
			}
			$dependencies[ $ref ] = true;
			if ( is_callable( [ $dependency, 'export' ] ) ) {
				$dep_export = $dependency->export();
				foreach ( $dep_export as $key => $normalized ) {
					$export[ $key ] = $normalized;
				}
			} else {
				$export[ $dependency->getRef() ] = $normalizer->normalize( $dependency );;
			}
		}

		return $export;
	}

	public function __call( string $name, array $arguments )
	{
		if ( ! is_callable( [ $this->entity, $name ] ) ) {
			throw new \Exception();
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
			$entities = $repository->findBy( $query );
		} else {
			$entities = $repository->findAll();
		}

		$models = [];
		foreach ( $entities as $entity ) {
			$models[] = new static( $entity );
		}

		return $models;
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
