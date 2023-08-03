<?php

namespace App\Service;

use App\Controller\EntityController;
use App\Model\AutomationModel;
use App\Model\FlowModel;
use App\Model\StepModel;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ModelNormalizer
{
	private $serializer;

	public function normalize( $model, $dependencies = false, $dependents = false ): array
	{
		if ( ! is_object( $model ) ) {
			// Other.
			return (array) $this->getSerializer()->normalize( $model );
		}

		$entity = $model->getEntity();

		$classRef       = EntityController::getEntityReflection( $entity );
		$propertyAccess = new PropertyAccessor();

		$data = [
			'_entity' => $classRef->getShortName(),
		];

		foreach ( $classRef->getProperties() as $property ) {
			$name   = $property->getName();
			$getter = 'get' . ucfirst( $name );

			if ( ! $dependencies ) {
				$value = $propertyAccess->getValue( $entity, $name );
				if ( is_object( $value ) ) {
					if ( is_iterable( $value ) ) {
						foreach ( $value as $key => $val ) {
							if ( is_object( $val ) ) {
								$value[ $key ] = $val->getId();
							}
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

				if ( is_iterable( $value ) ) {
					foreach ( $value as $key => $val ) {
						if ( is_object( $val ) && method_exists( $val, 'getEntity' ) ) {
							$value[ $key ] = $val->getEntity();
						}
					}
				} elseif ( is_object( $value ) && method_exists( $value, 'getEntity' ) ) {
					$value = $value->getEntity();
				}
			}

			$data[ $name ] = $value;
		}

		if ( $dependencies && method_exists( $model, 'getConfigEntityDependencies' ) ) {
			$dependencies          = $model->getConfigEntityDependencies();
			$data['_dependencies'] = [];
			foreach ( $dependencies as $dependency ) {
				$data['_dependencies'][] = $dependency->normalize( false, false );
			}
		}

		if ( $dependents ) {
			$data['_dependents'] = $this->getDependents( $model );
		}

		return $this->getSerializer()->normalize( $data );
	}

	public function getDependents( $model ): array
	{
		$dependents = [];

		$modelClass = EntityController::getEntityReflection( $model->getEntity() )->getShortName();

		$configModels = [
			'automation' => AutomationModel::class,
			//'connection' => ConnectionModel::class,
			'flow'       => FlowModel::class,
			'step'       => StepModel::class,
			//'dataset'    => DatasetModel::class,
		];

		foreach ( $configModels as $name => $configModel ) {
			$results = $configModel::getAll( [
				'search' => [
					'config' => strtolower( $modelClass ) . ':' . $model->getId(),
				],
			] );

			if ( $results ) {
				foreach ( $results as $dependent ) {
					$dependents[] = $dependent->normalize( false, false );
				}
			}
		}

		return $dependents;
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
