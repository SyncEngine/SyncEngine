<?php

namespace App\Service;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ModelExporter
{
	private $model;
	private static $dependencies = [];
	private static $running = false;

	public function __construct( $model )
	{
		$this->model = $model;
	}

	public function start( $key ): void
	{
		if ( ! self::$running ) {
			self::$running = $key;
		}
	}

	public function reset( $key ): void
	{
		if ( $key === self::$running ) {
			self::$running = false;
			self::$dependencies = [];
		}
	}

	public function export(): array
	{
		$model = $this->model;
		if ( ! $model ) {
			return [];
		}
		$entity = $model->getEntity();

		$key            = ( is_callable( [ $model, 'getRef' ] ) ) ? $model->getRef() : '_';
		$classRef       = \Doctrine\Common\Util\ClassUtils::newReflectionClass( get_class( $entity ) );
		$propertyAccess = new PropertyAccessor();
		$normalizer     = $this->getNormalizer();
		$export         = [ $key => [
			'_entity' => $classRef->getShortName(),
		] ];

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
							$modelClass = $this->getModelClass( $relation );
							if ( method_exists( $relation, 'getRef' ) && class_exists( $modelClass ) ) {
								$relRef = $relation->getRef();
								if ( ! isset( self::$dependencies[ $relRef ] ) ) {
									self::$dependencies[ $relRef ] = $modelClass::get( $relation->getId() );
								}
								$relation = $relRef;
							} else {
								$relation = $normalizer->normalize( $relation );
							}
							$value[ $relKey ] = $relation;
						}
					} else {
						$modelClass = $this->getModelClass( $value );
						if ( method_exists( $value, 'getRef' ) && class_exists( $modelClass ) ) {
							$valRef = $value->getRef();
							if ( ! isset( self::$dependencies[ $valRef ] ) ) {
								self::$dependencies[ $valRef ] = $modelClass::get( $value->getId() );
							}
							$value = $valRef;
						} else {
							$value = $normalizer->normalize( $value );
						}
					}
				} elseif ( is_array( $value ) ) {
					if ( method_exists( $model::class, 'getFields' ) ) {
						$value = $this->parseConfigFields( $value, $model::getFields() ?? [] );
					} elseif ( method_exists( $model, 'getFields' ) ) {
						$value = $this->parseConfigFields( $value, $model->getFields() ?? [] );
					} else {
						$value = $normalizer->normalize( $value );
					}
				}
			}
			$export[ $key ][ $property->name ] = $value;
		}

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
				$export[ $ref ] = $normalizer->normalize( $dependency );;
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
			$name   = $field['name'] ?? $key;
			$value  = $config[ $name ] ?? null;

			// Pull entities from fields config.
			if ( ! empty( $field['type'] ) && $value ){
				switch ( $field['type'] ) {
					case 'entity':
						$entity = $field['entity'] ?? '';
						if ( $entity ) {
							$entityId = ( is_numeric( $value ) ) ? $value : $value['id'] ?? 0;
							$entityModel = $this->getModelClass( ucfirst( $entity ) );
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

					case 'tasks':
						foreach ( $value as $taskKey => $taskConfig ) {
							$taskModel = TaskService::getTask( $taskConfig['_class'] );
							$config[ $name ][ $taskKey ] = $this->parseConfigFields( $taskConfig, $taskModel->getFields() );
						}
						break;

					case 'webservice':
						$webserviceModel = WebserviceService::getWebservice( $value['_class'] );
						$config[ $name ] = $this->parseConfigFields( $config[ $name ], $webserviceModel->getFields() );
						break;
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

	public function getModelClass( $class ): string
	{
		if ( is_object( $class ) ) {
			$class = ( new \ReflectionClass( $class ) )->getShortName();
		}
		return '\\App\\Model\\' . $class . 'Model';
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
