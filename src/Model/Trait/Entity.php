<?php

namespace App\Model\Trait;

use App\Controller\DefaultController;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Config\Definition\Exception\Exception;
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

		return ( new Serializer( [ $normalizer ] ) )->normalize( $this->entity );
	}

	public function export(): array
	{
		$dependencies = [];

		$defaultContext = [
			AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ( object $object ) use ( &$dependencies ): string|array {
				if ( is_callable( [ $object, 'getRef' ] ) ) {
					$dependencies[] = $object;
					return $object->getRef();
				}
				return $object->getId();
			},
		];

		$normalizer = new ObjectNormalizer( null, null, null, null, null, null, $defaultContext );
		$serializer = new Serializer( [ $normalizer ] );

		$key = ( is_callable( [ $this, 'getRef' ] ) ) ? $this->getRef() : '_';
		$export = [];
		$export[ $key ] = $serializer->normalize( $this->entity );

		foreach ( $dependencies as $dependency ) {
			if ( is_callable( [ $dependency, 'export' ] ) ) {
				$dep_export = $dependency->export();
				foreach ( $dep_export as $key => $normalized ) {
					$export[ $key ] = $normalized;;
				}
			} else {
				$export[ $dependency->getRef() ] = $serializer->normalize( $dependency );;
			}
		}

		return $export;
	}

	public function __call( string $name, array $arguments )
	{
		if ( ! is_callable( [ $this->entity, $name ] ) ) {
			throw new Exception();
		}

		return call_user_func_array( [ $this->entity, $name ], $arguments );
	}

	public static function get( $entity ): static|null
	{
		if ( $entity instanceof static ) {
			return $entity;
		}

		if ( empty( static::ENTITY ) ) {
			return null;
		}

		if ( $entity instanceof static::ENTITY ) {
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

	public static function query( array $query = [] ): array
	{
		if ( empty( static::ENTITY ) ) {
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
			$models[] = static::get( $entity );
		}

		return $models;
	}

	public static function getRepository(): EntityRepository
	{
		return DefaultController::getEntityManager()->getRepository( static::ENTITY );
	}
}
