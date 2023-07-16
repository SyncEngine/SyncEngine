<?php

namespace App\Model\Trait;

use Doctrine\ORM\EntityManagerInterface;
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

		$export = [
			$serializer->normalize( $this->entity ),
		];

		foreach ( $dependencies as $dependency ) {
			$export[] = $serializer->normalize( $dependency );
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
}
