<?php

namespace App\Service;

use App\Controller\EntityController;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ModelNormalizer
{
	private $serializer;

	public function normalize( $model, $full = false ): array
	{
		if ( ! is_object( $model ) ) {
			return $this->getSerializer()->normalize( $model );
		}
		$entity = $model->getEntity();

		$ref            = ( is_callable( [ $model, 'getRef' ] ) ) ? $model->getRef() : '_';
		$classRef       = EntityController::getEntityReflection( $entity );
		$propertyAccess = new PropertyAccessor();

		$data = [
			'_entity' => $classRef->getShortName(),
		];

		foreach ( $classRef->getProperties() as $property ) {
			$name   = $property->getName();
			$getter = 'get' . ucfirst( $name );

			if ( ! $full ) {
				$value = $propertyAccess->getValue( $entity, $name );
				if ( is_object( $value ) ) {
					if ( is_iterable( $value ) ) {
						foreach ( $value as $key => $val ) {
							$value[ $key ] = $val->getId();
						}
					} else {
						$value = $value->getId();
					}
				}
			} else {
				if ( is_callable( [ $entity, $getter ] ) ) {
					// Call Model method instead of entity to allow context overrides.
					$value = call_user_func( [ $model, $getter ] );
				} else {
					$value = $propertyAccess->getValue( $entity, $name );
				}
			}

			$data[ $name ] = $value;
		}

		if ( $full ) {
			// Get dependents.
		}

		return $this->getSerializer()->normalize( $data );
	}

	public function getSerializer( $normalizers = [] ): Serializer
	{
		if ( $normalizers ) {
			return new Serializer( $normalizers );
		}

		if ( $this->serializer instanceof Serializer ) {
			return $this->serializer;
		}

		$this->serializer = new Serializer( [ $this->getNormalizer() ] );

		return $this->serializer;
	}

	public function getNormalizer(): ObjectNormalizer
	{
		$defaultContext = [
			AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ( object $object ): string {
				return $object->getId();
			},
		];

		return new ObjectNormalizer( null, null, null, null, null, null, $defaultContext );
	}
}
