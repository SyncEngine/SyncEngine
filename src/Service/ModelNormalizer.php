<?php

namespace SyncEngine\Service;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Normalizable;
use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\StorageModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\Tag\TagExtractor;

class ModelNormalizer
{
	private $serializer;
	private static ?string $runningRef = null;
	private static array $normalized = [];
	private static array $tagRefs = [];

	private function start( string $ref ): void
	{
		if ( ! self::$runningRef ) {
			self::$runningRef = $ref;
		}
	}

	private function reset( string $ref ): void
	{
		if ( $ref === self::$runningRef ) {
			self::$runningRef = null;
			self::$normalized = [];
			self::$tagRefs    = [];
		}
	}

	public function normalize( $model, $dependencies = false, $dependents = false ): array
	{
		if ( ! $model instanceof EntityModel ) {
			// Other.
			return (array) $this->getSerializer()->normalize( $model );
		}

		$currentRef = ( method_exists( $model, 'getRef' ) ) ? $model->getRef() : '_';

		if ( $currentRef === self::$runningRef ) {
			return [];
		}

		$this->start( $currentRef );

		// Get entity without ref.
		$entity = clone $model->getEntity();

		$classRef       = EntityModel::getEntityReflection( $entity );
		$propertyAccess = new PropertyAccessor();

		$data = [
			'_entity' => $classRef->getShortName(),
			'_supports' => [
				'config'     => $model instanceof Configurable,
				'tags'       => $model instanceof Taggable,
				'supervisor' => $model instanceof Supervisable,
				'blueprints' => $model instanceof Supervisable && $model->supportsSupervisor( 'blueprint' ),
			],
		];

		foreach ( $classRef->getProperties() as $propertyRef ) {
			$name   = $propertyRef->getName();
			$getter = 'get' . ucfirst( $name );

			if ( $propertyRef->getAttributes( Ignore::class, \ReflectionAttribute::IS_INSTANCEOF ) ) {
				continue;
			}

			if ( ! $dependencies ) {
				$value = $propertyAccess->getValue( $entity, $name );
				if ( is_object( $value ) ) {
					// Remove ref.
					$value = clone $value;

					if ( is_iterable( $value ) ) {
						foreach ( $value as $key => $val ) {
							if ( method_exists( $val, 'getId' ) ) {
								$value[ $key ] = $val->getId();
							}
						}
					} elseif ( method_exists( $value, 'getId' ) ) {
						$value = $value->getId();
					}
				}
			} else {
				if ( is_callable( [ $model, $getter ] ) ) {
					$methodRef = $classRef->getMethod( $getter );
					if ( $methodRef->getAttributes( Ignore::class, \ReflectionAttribute::IS_INSTANCEOF ) ) {
						continue;
					}

					// Call Model method instead of entity to allow context overrides.
					$value = call_user_func( [ $model, $getter ] );
				} else {
					$value = $propertyAccess->getValue( $entity, $name );
				}

				if ( is_object( $value ) ) {
					$valueRef = new \ReflectionClass( $value );
					if ( $valueRef->isEnum() ) {
						$value = $value->value;
					} elseif ( $valueRef->isCloneable() ) {
						// Remove ref.
						$value = clone $value;
					}
				}

				if ( is_iterable( $value ) ) {
					foreach ( $value as $key => $val ) {
						if ( $val instanceof Normalizable ) {
							$value[ $key ] = $val->normalize();
						}
					}
				} elseif ( $value instanceof Normalizable ) {
					$value = $value->normalize();
				}
			}

			if ( $value instanceof \DateTimeInterface ) {
				$value = $value->getTimestamp();
			}

			$data[ $name ] = $value;
		}

		if ( $model instanceof Taggable ) {
			$data['tags'] = $model->getTags();
		}

		if ( $dependencies && method_exists( $model, 'getConfigDependencies' ) ) {
			$dependencies          = $model->getConfigDependencies();
			$data['_dependencies'] = [];
			foreach ( $dependencies as $key => $dependency ) {
				$ref = $dependency->getRef();
				if ( ! isset( static::$normalized[ $ref ] ) ) {
					static::$normalized[ $ref ] = $this->normalize( $dependency, false, false );
				}
				$data['_dependencies'][ $key ] = static::$normalized[ $ref ];
			}
		}

		if ( $dependents ) {
			$data['_dependents'] = $this->getDependents( $model );
		}

		$this->reset( $currentRef );

		return $this->getSerializer()->normalize( $data );
	}

	public function getConfigDependencies( array $config = [], array $fields = [], array|bool $recursive = false ): array
	{
		$dependencies = [];
		if ( $recursive ) {
			$dependencies = is_array( $recursive ) ? $recursive : [];
		}

		if ( ! $config || ! $fields ) {
			return $dependencies;
		}

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$name  = $field['name'] ?? $key;
			$value = $config[ $name ] ?? null;

			// Parse subfields from fields config.
			if ( ! empty( $field['type'] ) && $value ) {
				switch ( $field['type'] ) {
					case 'entity':
						$entity = $field['entity'] ?? '';
						if ( $entity ) {
							$dependencies = $this->getEntityDependency( $entity, $value, $dependencies );
						}
					break;
					case 'entities':
						$entity = $field['entity'] ?? '';
						if ( $entity ) {
							foreach ( $value as $id ) {
								$dependencies = $this->getEntityDependency( $entity, $id, $dependencies );
							}
						}
					break;

					case 'tasks':
						foreach ( $value as $taskConfig ) {
							$taskModel = TaskModel::get( $taskConfig['_class'] );
							if ( $taskModel ) {
								$dependencies = $this->getConfigDependencies( $taskConfig, $taskModel->getFields(), $dependencies );
							} else {
								// @todo Error.
							}
						}
					break;

					case 'webservice':
						$webserviceModel = WebserviceModel::get( $value['_class'] );
						if ( $webserviceModel ) {
							$dependencies    = $this->getConfigDependencies( $config[ $name ], $webserviceModel->getFields(), $dependencies );
						} else {
							// @todo Error.
						}
					break;

					case 'repeater':
						foreach ( $value as $repeaterConfig ) {
							$dependencies = $this->getConfigDependencies( $repeaterConfig, $field['fieldset'] ?? [], $dependencies );
						}
						unset( $field['fieldset'] );
					break;

					case 'schema':
						if ( is_array( $value ) ) {
							$dependencies = $this->getConfigSchemaDependencies( $value, $dependencies );
						}
					break;
				}
			}

			if ( ! empty( $field['nested'] ) ) {
				$dependencies = $this->getConfigDependencies( (array) $value, $field['nested'], $dependencies );
				unset( $field['nested'] );
			}

			// @todo Check for specific keys?
			$dependencies = $this->getConfigDependencies( $config, $field, $dependencies );
		}

		// @todo Autowiring.
		$tagExtractor = new TagExtractor();
		$tags = $tagExtractor->extractTags( $config, 'storage' );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$ref = $tagExtractor->getTagPart( $tag, 1 );
				if ( ! $ref ) {
					continue;
				}

				// Get the already parsed ref.
				if ( isset( self::$tagRefs[ $ref ] ) ) {
					$ref = self::$tagRefs[ $ref ];
				}

				if ( ! isset( static::$tagRefs[ $ref ] ) ) {
					$storageModel = StorageModel::get( $ref );
					if ( ! $storageModel ) {
						self::$tagRefs[ $ref ] = false;
						continue;
					}

					// Cache ref/id and point it to the actual ref.
					self::$tagRefs[ $ref ] = $storageModel->getRef();

					// @todo Create utility for adding dependencies?
					if ( ! isset( $dependencies[ 'storage:' . $storageModel->getId() ] ) ) {
						$dependencies[ 'storage:' . $storageModel->getId() ] = $storageModel;
						if ( $recursive ) {
							$dependencies = $storageModel->getConfigDependencies( $dependencies );
						}
					}
				}
			}
		}

		ksort( $dependencies );

		return $dependencies;
	}

	public function getConfigSchemaDependencies( array $config = [], array|bool $recursive = false )
	{
		$dependencies = [];
		if ( $recursive ) {
			$dependencies = is_array( $recursive ) ? $recursive : [];
		}

		if ( ! empty( $config['storage'] ) && is_numeric( $config['storage'] ) && 'storage' === ( $config['source'] ?? '' ) ) {
			$dependencies = $this->getEntityDependency( 'storage', $config['storage'], $dependencies );
		} else {
			foreach ( $config as $value ) {
				if ( is_array( $value ) ) {
					$dependencies = $this->getConfigSchemaDependencies( $value, $dependencies );
				}
			}
		}

		return $dependencies;
	}

	public function getEntityDependency( string $entity, mixed $id, array|bool $recursive = [] )
	{
		$dependencies = [];
		if ( $recursive ) {
			$dependencies = is_array( $recursive ) ? $recursive : [];
		}

		$entityModel = EntityModel::get( $id, $entity );
		$entity      = strtolower( $entity );

		if ( $entityModel instanceof Persistable && ! isset( $dependencies[ $entity . ':' . $entityModel->getId() ] ) ) {

			$dependencies[ $entity . ':' . $entityModel->getId() ] = $entityModel;
			if ( $recursive && method_exists( $entityModel, 'getConfigEntityDependencies' ) ) {
				$dependencies = $entityModel->getConfigDependencies( $dependencies );
			}
		}

		return $dependencies;
	}

	public function getDependents( $model ): array
	{
		$dependents = [];

		$modelClass = EntityModel::getEntityReflection( $model->getEntity() )->getShortName();

		$configModels = [
			'automation' => AutomationModel::class,
			//'connection' => ConnectionModel::class,
			'flow'       => FlowModel::class,
			'routine'    => RoutineModel::class,
			'storage'    => StorageModel::class,
		];

		foreach ( $configModels as $name => $configModel ) {
			$results = $configModel::getAll( [
				'search' => [
					'config' => '"' . strtolower( $modelClass ) . ':' . $model->getId() . '"',
				],
			] );

			if ( $results ) {
				foreach ( $results as $dependent ) {
					$ref = $dependent->getRef();
					if ( ! isset( static::$normalized[ $ref ] ) ) {
						static::$normalized[ $ref ] = $dependent->normalize( false, false );
					}
					$dependents[] = static::$normalized[ $ref ];
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
