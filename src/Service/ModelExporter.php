<?php

namespace SyncEngine\Service;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use SyncEngine\Attribute\NotExportable;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\StorageModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\Tag\TagExtractor;

class ModelExporter
{
	private Serializer $serializer;
	private static ?string $runningRef = null;
	private static array $dependencies = [];
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
			self::$runningRef   = null;
			self::$dependencies = [];
			self::$tagRefs      = [];
		}
	}

	public function export( $model, $exportDependencies = true ): array
	{
		if ( ! $model ) {
			return [];
		}

		// Get entity without ref.
		$entity = clone $model->getEntity();

		$currentRef = ( is_callable( [ $model, 'getRef' ] ) ) ? $model->getRef() : '_';

		if ( $currentRef === self::$runningRef ) {
			return [];
		}

		$classRef       = EntityModel::getEntityReflection( $entity );
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

					$valueRef = new \ReflectionClass( $value );
					if ( $valueRef->isEnum() ) {
						$value = $value->value;
					} elseif ( $valueRef->isCloneable() ) {
						// Remove ref.
						$value = clone $value;
					}

					if ( is_iterable( $value ) ) {
						// Doctrine collections.
						$iterable = $value;
						$value    = [];
						foreach ( $iterable as $relKey => $relation ) {
							$modelClass = EntityModel::getEntityModelClass( $relation );
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
						$modelClass = EntityModel::getEntityModelClass( $value );
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
							$config[ $name ] = $this->parseConfigEntity( $entity, $value );
						}
					break;
					case 'entities':
						$entity = $field['entity'] ?? '';
						if ( $entity ) {
							foreach ( $value as $index => $id ) {
								$value[ $index ] = $this->parseConfigEntity( $entity, $id );
							}
							$config[ $name ] = $value;
						}
					break;

					case 'tasks':
						foreach ( $value as $taskKey => $taskConfig ) {
							$taskModel = TaskModel::get( $taskConfig['_class'] );
							if ( $taskModel ) {
								$config[ $name ][ $taskKey ] = $this->parseConfigFields( $taskConfig, $taskModel->getFields() );
							} else {
								// @todo Error.
							}
						}
					break;

					case 'webservice':
						$webserviceModel = WebserviceModel::get( $value['_class'] );
						if ( $webserviceModel ) {
							$config[ $name ] = $this->parseConfigFields( $config[ $name ], $webserviceModel->getFields() );
						} else {
							// @todo Error.
						}
					break;

					case 'repeater':
						foreach ( $value as $repeaterKey => $repeaterConfig ) {
							$config[ $name ][ $repeaterKey ] = $this->parseConfigFields( $repeaterConfig, $field['fieldset'] );
						}
						unset( $field['fieldset'] );
					break;

					case 'schema':
						if ( is_array( $value ) ) {
							$value = $this->parseConfigSchema( $value );
						}
					break;
				}
			}

			if ( ! empty( $field['nested'] ) ) {
				$config[ $name ] = is_array( $value ) ? $this->parseConfigFields( $value, $field['nested'] ) : $value;
				unset( $field['nested'] );
			}

			// @todo Check for specific keys?
			if ( is_array( $field ) ) {
				$config = $this->parseConfigFields( $config, $field );
			}
		}

		return $this->parseConfigTags( $config );
	}

	public function parseConfigTags( array $config ): array
	{
		$tagExtractor = new TagExtractor();
		$tags = $tagExtractor->extractTags( $config, 'storage' );

		if ( ! $tags ) {
			return $config;
		}

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
				$storageModel = StorageModel::get( $ref );
				if ( ! $storageModel ) {
					self::$tagRefs[ $ref ] = false;
					continue;
				}

				// Cache ref/id and point it to the actual ref.
				self::$tagRefs[ $ref ] = $storageModel->getRef();

				if ( ! isset( self::$dependencies[ $storageModel->getRef() ] ) ) {
					self::$dependencies[ $storageModel->getRef() ] = $storageModel;
				}
			}
		}

		// @todo improve this.
		$replace_tags = [];
		foreach ( self::$tagRefs as $id_tag => $ref_tag ) {
			if ( ! is_numeric( $id_tag ) ) {
				continue;
			}
			$replace_tags[ '{{ storage.'.$id_tag.' }}' ] = '{{ storage.'.$ref_tag.' }}'; // Both space.
			$replace_tags[ '{{storage.'.$id_tag.'}}' ] = '{{storage.'.$ref_tag.'}}'; // No space.
			$replace_tags[ '{{ storage.'.$id_tag.'}}' ] = '{{ storage.'.$ref_tag.'}}'; // Begin space.
			$replace_tags[ '{{storage.'.$id_tag.' }}' ] = '{{storage.'.$ref_tag.' }}'; // End space.
			$replace_tags[ '{{ storage.'.$id_tag.'.' ] = '{{ storage.'.$ref_tag.'.'; // Traverse begin space.
			$replace_tags[ '{{storage.'.$id_tag.'.' ] = '{{storage.'.$ref_tag.'.'; // Traverse no space.
			// And with quotes.
			$replace_tags[ '{{ storage."'.$id_tag.'" }}' ] = '{{ storage."'.$ref_tag.'" }}'; // Both space.
			$replace_tags[ '{{storage."'.$id_tag.'"}}' ] = '{{storage."'.$ref_tag.'"}}'; // No space.
			$replace_tags[ '{{ storage."'.$id_tag.'"}}' ] = '{{ storage."'.$ref_tag.'"}}'; // Begin space.
			$replace_tags[ '{{storage."'.$id_tag.'" }}' ] = '{{storage."'.$ref_tag.'" }}'; // End space.
			$replace_tags[ '{{ storage."'.$id_tag.'".' ] = '{{ storage."'.$ref_tag.'".'; // Traverse begin space.
			$replace_tags[ '{{storage."'.$id_tag.'".' ] = '{{storage."'.$ref_tag.'".'; // Traverse no space.
		}
		if ( ! empty( $replace_tags ) ) {
			array_walk_recursive( $config, function ( &$value ) use ( $replace_tags ) {
				$value = str_replace( array_keys( $replace_tags ), array_values( $replace_tags ), $value );
			} );
		}

		return $config;
	}

	public function parseConfigSchema( array $config )
	{
		if ( ! empty( $config['storage'] ) && is_numeric( $config['storage'] ) && 'storage' === ( $config['source'] ?? '' ) ) {
			$config = $this->parseConfigEntity( 'storage', $config['storage'] );
		} else {
			foreach ( $config as $value ) {
				if ( is_array( $value ) ) {
					$config = $this->parseConfigSchema( $value );
				}
			}
		}

		return $config;
	}

	public function parseConfigEntity( string $entity, mixed $value )
	{
		$entity      = strtolower( $entity );
		$entityModel = EntityModel::getEntityModelClass( ucfirst( $entity ) );

		if ( class_exists( $entityModel ) ) {
			$entityId    = ( is_numeric( $value ) ) ? $value : $value['id'] ?? 0;
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
			}
		}

		return $value;
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

		if ( isset( $this->serializer ) ) {
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
