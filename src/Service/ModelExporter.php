<?php

namespace SyncEngine\Service;

use SyncEngine\Attribute\NotExportable;
use SyncEngine\Controller\Abstract\EntityController;
use SyncEngine\Model\DatasetModel;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ModelExporter
{
	private $serializer;
	private static $runningRef = false;
	private static $dependencies = [];
	private static $tagRefs = [];

	public function __construct(
		private readonly Tasks $tasksService,
		private readonly Webservices $webservicesService,
	) {}

	private function start( $key ): void
	{
		if ( ! self::$runningRef ) {
			self::$runningRef = $key;
		}
	}

	private function reset( $key ): void
	{
		if ( $key === self::$runningRef ) {
			self::$runningRef   = false;
			self::$dependencies = [];
			self::$tagRefs      = [];
		}
	}

	public function export( $model, $exportDependencies = true ): array
	{
		if ( ! $model ) {
			return [];
		}
		$entity = $model->getEntity();

		$currentRef = ( is_callable( [ $model, 'getRef' ] ) ) ? $model->getRef() : '_';

		if ( $currentRef === self::$runningRef ) {
			return [];
		}

		$classRef       = EntityController::getEntityReflection( $entity );
		$propertyAccess = new PropertyAccessor();
		$export         = [
			$currentRef => [
				'_entity' => $classRef->getShortName(),
			],
		];

		$this->start( $currentRef );

		foreach ( $classRef->getProperties() as $property ) {
			if ( $property->getAttributes( NotExportable::class, \ReflectionAttribute::IS_INSTANCEOF ) ) {
				continue;
			}

			$getter = 'get' . ucfirst( $property->getName() );
			if ( is_callable( [ $entity, $getter ] ) ) {
				// Call Model method instead of entity to allow context overrides.
				$value = call_user_func( [ $model, $getter ] );
			} else {
				$value = $propertyAccess->getValue( $entity, $property->getName() );
			}
			if ( $value ) {
				if ( is_object( $value ) ) {
					if ( is_iterable( $value ) ) {
						// Doctrine collections.
						$iterable = $value;
						$value    = [];
						foreach ( $iterable as $relKey => $relation ) {
							$modelClass = EntityController::getEntityModelClass( $relation );
							if ( method_exists( $relation, 'getRef' ) && class_exists( $modelClass ) ) {
								$relRef = $relation->getRef();
								if ( ! isset( self::$dependencies[ $relRef ] ) ) {
									self::$dependencies[ $relRef ] = $modelClass::get( $relation->getId() );
								}
								$relation = $relRef;
							} else {
								$relation = $this->normalize( $relation );
							}
							$value[ $relKey ] = $relation;
						}
					} else {
						$modelClass = EntityController::getEntityModelClass( $value );
						if ( method_exists( $value, 'getRef' ) && class_exists( $modelClass ) ) {
							$valRef = $value->getRef();
							if ( ! isset( self::$dependencies[ $valRef ] ) ) {
								self::$dependencies[ $valRef ] = $modelClass::get( $value->getId() );
							}
							$value = $valRef;
						} else {
							$value = $this->normalize( $value );
						}
					}
				} elseif ( is_array( $value ) ) {
					if ( method_exists( $model, 'getFields' ) ) {
						$value = $this->parseConfigFields( $value, $model->getFields() ?? [] );
					} else {
						$value = $this->normalize( $value );
					}
				}
			}
			$export[ $currentRef ][ $property->name ] = $value;
		}

		if ( $exportDependencies ) {
			foreach ( self::$dependencies as $ref => $dependency ) {
				if ( ! is_object( $dependency ) ) {
					continue;
				}
				self::$dependencies[ $ref ] = true;
				if ( method_exists( $dependency, 'export' ) ) {
					$dep_export = $dependency->export();
					foreach ( $dep_export as $key => $normalized ) {
						$export[ $key ] = $normalized;
					}
				} else {
					$export[ $ref ] = $this->normalize( $dependency );;
				}
			}
		}

		$this->reset( $currentRef );

		return $export;
	}

	public function parseConfigFields( array $config, array $fields ): array
	{
		if ( ! $fields ) {
			return $config;
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
							if ( $entityModel && method_exists( $entityModel, 'getRef' ) ) {
								// Set new dependency.
								if ( ! isset( self::$dependencies[ $entityModel->getRef() ] ) ) {
									self::$dependencies[ $entityModel->getRef() ] = $entityModel;
								}
								// Override config.
								if ( is_numeric( $value ) ) {
									$value = $entityModel->getRef();
								} else {
									$value['id'] = $entityModel->getRef();
								}
								$config[ $name ] = $value;
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
								if ( $entityModel && method_exists( $entityModel, 'getRef' ) ) {
									// Set new dependency.
									if ( ! isset( self::$dependencies[ $entityModel->getRef() ] ) ) {
										self::$dependencies[ $entityModel->getRef() ] = $entityModel;
									}
									// Override config.
									if ( is_numeric( $value ) ) {
										$value = $entityModel->getRef();
									} else {
										$value['id'] = $entityModel->getRef();
									}
									$config[ $name ] = $value;
								}
							}
						}
					break;

					case 'tasks':
						foreach ( $value as $taskKey => $taskConfig ) {
							$taskModel                   = $this->tasksService->get( $taskConfig['_class'] );
							if ( $taskModel ) {
								$config[ $name ][ $taskKey ] = $this->parseConfigFields( $taskConfig, $taskModel->getFields() );
							} else {
								// @todo Error.
							}
						}
					break;

					case 'webservice':
						$webserviceModel = $this->webservicesService->get( $value['_class'] );
						if ( $webserviceModel ) {
							$config[ $name ] = $this->parseConfigFields( $config[ $name ], $webserviceModel->getFields() );
						} else {
							// @todo Error.
						}
					break;

					case 'repeater':
						foreach ( $value as $repeaterKey => $repeaterConfig ) {
							$config[ $name ][ $repeaterKey ] = $this->parseConfigFields( $repeaterConfig, $field['fieldset'] );
							unset( $field['fieldset'] );
						}
					break;
				}
			}

			if ( ! empty( $field['nested'] ) && $value ) {
				$config[ $name ] = $this->parseConfigFields( $value, $field['nested'] );
				unset( $field['nested'] );
			}

			// @todo Check for specific keys?
			if ( is_array( $field ) ) {
				$config = $this->parseConfigFields( $config, $field );
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

				if ( ! isset( self::$dependencies[ $ref ] ) ) {
					$datasetModel = DatasetModel::get( $ref );
					if ( ! $datasetModel ) {
						self::$tagRefs[ $ref ] = false;
						continue;
					}

					// Cache ref/id and point it to the actual ref.
					self::$tagRefs[ $ref ] = $datasetModel->getRef();

					if ( ! isset( self::$dependencies[ $datasetModel->getRef() ] ) ) {
						self::$dependencies[ $datasetModel->getRef() ] = $datasetModel;
					}
				}
			}
		}

		return $config;
	}

	public function normalize( $data ): array
	{
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
				return $object->getRel();
			},
		];

		return new ObjectNormalizer( null, null, null, null, null, null, $defaultContext );
	}
}
