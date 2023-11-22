<?php

namespace App\Service;

use App\Controller\Abstract\EntityController;
use App\Model\DatasetModel;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ModelExporter
{
	private static $dependencies = [];
	private static $running = false;
	private $serializer;

	private function start( $key ): void
	{
		if ( ! self::$running ) {
			self::$running = $key;
		}
	}

	private function reset( $key ): void
	{
		if ( $key === self::$running ) {
			self::$running      = false;
			self::$dependencies = [];
		}
	}

	public function export( $model, $exportDependencies = true ): array
	{
		if ( ! $model ) {
			return [];
		}
		$entity = $model->getEntity();

		$key            = ( is_callable( [ $model, 'getRef' ] ) ) ? $model->getRef() : '_';
		$classRef       = EntityController::getEntityReflection( $entity );
		$propertyAccess = new PropertyAccessor();
		$export         = [
			$key => [
				'_entity' => $classRef->getShortName(),
			],
		];

		$this->start( $key );

		foreach ( $classRef->getProperties() as $property ) {
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
			$export[ $key ][ $property->name ] = $value;
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

		$this->reset( $key );

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
							$taskModel                   = Tasks::getTask( $taskConfig['_class'] );
							$config[ $name ][ $taskKey ] = $this->parseConfigFields( $taskConfig, $taskModel->getFields() );
						}
					break;

					case 'webservice':
						$webserviceModel = Webservices::getWebservice( $value['_class'] );
						$config[ $name ] = $this->parseConfigFields( $config[ $name ], $webserviceModel->getFields() );
					break;
				}
			}

			if ( ! empty( $field['taggable'] ) && $value ) {
				$tagExtractor = new TagExtractor();
				$tags = $tagExtractor->extractTags( $value, 'dataset' );
				if ( $tags ) {
					foreach ( $tags as $tag ) {
						// @todo Create utility?
						$dataset = $tagExtractor->getTagParts( $tag )[1] ?? null;
						self::$dependencies[ $dataset ] = DatasetModel::get( $dataset );
					}
				}
			}

			if ( ! empty( $field['nested'] ) && $value ) {
				$config[ $name ] = $this->parseConfigFields( $value, $field['nested'] );
				unset( $field['nested'] );
			} elseif ( is_array( $field ) ) {
				$config = $this->parseConfigFields( $config, $field );
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
