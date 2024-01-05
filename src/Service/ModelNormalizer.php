<?php

namespace SyncEngine\Service;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use SyncEngine\Controller\Abstract\EntityController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\DatasetModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\StepModel;
use SyncEngine\Service\Provider\Tasks;
use SyncEngine\Service\Provider\Webservices;
use SyncEngine\Service\Tag\TagExtractor;

class ModelNormalizer
{
	private $serializer;
	private static ?string $runningRef = null;
	private static array $normalized = [];
	private static array $tagRefs = [];

	public function __construct(
		private readonly Tasks $tasksService,
		private readonly Webservices $webservicesService,
	) {}

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
		if ( ! is_object( $model ) ) {
			// Other.
			return (array) $this->getSerializer()->normalize( $model );
		}

		$currentRef = ( is_callable( [ $model, 'getRef' ] ) ) ? $model->getRef() : '_';

		if ( $currentRef === self::$runningRef ) {
			return [];
		}

		$this->start( $currentRef );

		$entity = $model->getEntity();

		$classRef       = EntityController::getEntityReflection( $entity );
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
					$value = clone $value;
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
					$methodRef = $classRef->getMethod( $getter );
					if ( $methodRef->getAttributes( Ignore::class, \ReflectionAttribute::IS_INSTANCEOF ) ) {
						continue;
					}

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
							$taskModel    = $this->tasksService->get( $taskConfig['_class'] );
							if ( $taskModel ) {
								$dependencies = $this->getConfigDependencies( $taskConfig, $taskModel->getFields(), $dependencies );
							} else {
								// @todo Error.
							}
						}
					break;

					case 'webservice':
						$webserviceModel = $this->webservicesService->get( $value['_class'] );
						if ( $webserviceModel ) {
							$dependencies    = $this->getConfigDependencies( $config[ $name ], $webserviceModel->getFields(), $dependencies );
						} else {
							// @todo Error.
						}
					break;

					case 'repeater':
						foreach ( $value as $repeaterConfig ) {
							$dependencies = $this->getConfigDependencies( $repeaterConfig, $field['fieldset'], $dependencies );
						}
						unset( $field['fieldset'] );
					break;
				}
			}

			if ( ! empty( $field['nested'] ) && $value ) {
				$dependencies = $this->getConfigDependencies( (array) $value, $field['nested'], $dependencies );
				unset( $field['nested'] );
			}

			// @todo Check for specific keys?
			if ( is_array( $field ) ) {
				$dependencies = $this->getConfigDependencies( $config, $field, $dependencies );
			}
		}

		// @todo Autowiring.
		$tagExtractor = new TagExtractor();
		$tags = $tagExtractor->extractTags( $config, 'dataset' );
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
					$datasetModel = DatasetModel::get( $ref );
					if ( ! $datasetModel ) {
						self::$tagRefs[ $ref ] = false;
						continue;
					}

					// Cache ref/id and point it to the actual ref.
					self::$tagRefs[ $ref ] = $datasetModel->getRef();

					// @todo Create utility for adding dependencies?
					if ( ! isset( $dependencies[ 'dataset:' . $datasetModel->getId() ] ) ) {
						$dependencies[ 'dataset:' . $datasetModel->getId() ] = $datasetModel;
						if ( $recursive ) {
							$dependencies = $datasetModel->getConfigDependencies( $dependencies );
						}
					}
				}
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
			'dataset'    => DatasetModel::class,
		];

		foreach ( $configModels as $name => $configModel ) {
			$results = $configModel::getAll( [
				'search' => [
					'config' => strtolower( $modelClass ) . ':' . $model->getId(),
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
