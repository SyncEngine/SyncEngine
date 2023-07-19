<?php

namespace App\Service;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ModelExporter
{
	private $rootModel;
	private static $dependencies = [];

	public function __construct( $rootModel )
	{
		$this->rootModel = $rootModel;
	}

	public function export()
	{
		static $running = [];
		$model = $this->rootModel;
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

		return $export;
	}

	public function getModelClass( $class ) {
		if ( is_object( $class ) ) {
			$class = ( new \ReflectionClass( $class ) )->getShortName();
		}
		return '\\App\\Model\\' . $class . 'Model';
	}

	public function getNormalizer()
	{
		$defaultContext = [
			AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ( object $object ): string {
				return $object->getId();
			},
		];
		return new ObjectNormalizer( null, null, null, null, null, null, $defaultContext );
	}
}
