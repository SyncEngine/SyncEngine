<?php

namespace App\Service;

use App\Controller\Abstract\EntityController;
use App\Model\AutomationModel;
use App\Model\FlowModel;
use App\Model\Interface\Persistable;
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

		if ( $dependencies && method_exists( $model, 'getConfigDependencies' ) ) {
			$dependencies          = $model->getConfigDependencies();
			$data['_dependencies'] = [];
			foreach ( $dependencies as $key => $dependency ) {
				$data['_dependencies'][ $key ] = $dependency->normalize( false, false );
			}
		}

		if ( $dependents ) {
			$data['_dependents'] = $this->getDependents( $model );
		}

		return $this->getSerializer()->normalize( $data );
	}

	public function parseConfigDependencies( array $config = [], array $fields = [], array|bool $recursive = false ): array
	{
		$dependencies = [];
		if ( $recursive ) {
			$dependencies = is_array( $recursive ) ? $recursive : [];
		}

		if ( ! $config || ! $fields ) {
			return $dependencies;
		}

		foreach ( $fields as $key => $field ) {
			$name  = $field['name'] ?? $key;
			$value = $config[ $name ] ?? null;

			// Pull entities from fields config.
			if ( ! empty( $field['type'] ) && $value ) {
				switch ( $field['type'] ) {
					case 'entity':
						$entity = $field['entity'] ?? '';
						if ( $entity ) {
							$entityId    = ( is_numeric( $value ) ) ? $value : $value['id'] ?? 0;
							$entityModel = EntityController::getEntityModelClass( ucfirst( $entity ) );
							$entityModel = $entityModel::get( $entityId );
							if ( $entityModel instanceof Persistable && ! isset( $dependencies[ $entity . ':' . $entityId ] ) ) {

								$dependencies[ $entity . ':' . $entityId ] = $entityModel;
								if ( $recursive && method_exists( $entityModel, 'getConfigEntityDependencies' ) ) {
									$dependencies = $entityModel->getConfigDependencies( $dependencies );
								}
							}
						}
					break;
					case 'entities':
						$entity = $field['entity'] ?? '';
						if ( $entity ) {
							foreach ( $value as $id ) {
								$entityId    = ( is_numeric( $id ) ) ? $id : $id['id'] ?? 0;
								$entityModel = EntityController::getEntityModelClass( ucfirst( $entity ) );
								$entityModel = $entityModel::get( $entityId );
								if ( $entityModel instanceof Persistable && ! isset( $dependencies[ $entity . ':' . $entityId ] ) ) {

									$dependencies[ $entity . ':' . $entityId ] = $entityModel;
									if ( $recursive && method_exists( $entityModel, 'getConfigEntityDependencies' ) ) {
										$dependencies = $entityModel->getConfigDependencies( $dependencies );
									}
								}
							}
						}
					break;

					case 'tasks':
						foreach ( $value as $taskConfig ) {
							$taskModel    = Tasks::getTask( $taskConfig['_class'] );
							$dependencies = $this->parseConfigDependencies( $taskConfig, $taskModel->getFields(), $dependencies );
						}
					break;

					case 'webservice':
						$webserviceModel = Webservices::getWebservice( $value['_class'] );
						$dependencies    = $this->parseConfigDependencies( $config[ $name ], $webserviceModel->getFields(), $dependencies );
					break;
				}
			}

			if ( ! empty( $field['nested'] ) && $value ) {
				$dependencies = $this->parseConfigDependencies( $value, $field['nested'], $dependencies );
				unset( $field['nested'] );
			} elseif ( is_array( $field ) ) {
				$dependencies = $this->parseConfigDependencies( $config, $field, $dependencies );
			}
		}

		ksort( $dependencies );

		return $dependencies;
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
