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
		$export         = [ $key => [] ];
		$classRef       = new \ReflectionClass( $entity );
		$propertyAccess = new PropertyAccessor();
		$normalizer     = $this->getNormalizer();

		foreach ( $classRef->getProperties() as $property ) {
			$getter = 'get' . ucfirst( $property->getName() );
			if ( is_callable( $entity, $getter ) ) {
				// Call Model method instead of entity to allow context overrides.
				$value = call_user_func( [ $model, $getter ] );
			} else {
				$value = $propertyAccess->getValue( $entity, $property->getName() );
			}
			if ( $value ) {
				if ( is_object( $value ) ) {
					if ( is_iterable( $value ) ) {
						$iterable = $value;
						$value    = [];
						foreach ( $iterable as $relKey => $relation ) {
							$modelClass = '\\App\\Model\\'
							              . ( new \ReflectionClass( $relation ) )->getShortName()
							              . 'Model';

							if ( is_callable( [ $relation, 'getRef' ] ) && class_exists( $modelClass ) ) {
								if ( ! isset( self::$dependencies[ $relation->getRef() ] ) ) {
									self::$dependencies[ $relation->getRef() ] = new $modelClass( $relation );
								}
								$relation = $relation->getRef();
							} else {
								$relation = $normalizer->normalize( $relation );
							}
							$value[ $relKey ] = $relation;
						}
					} else {
						$modelClass = '\\App\\Model\\' . ( new \ReflectionClass(  ) )->getShortName() . 'Model';
						if ( is_callable( [ $value, 'getRef' ] ) && class_exists( $modelClass ) ) {
							if ( ! isset( self::$dependencies[ $value->getRef() ] ) ) {
								self::$dependencies[ $value->getRef() ] = new $modelClass( $value );
							}
							$value = $value->getRef();
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
			if ( is_callable( [ $dependency, 'export' ] ) ) {
				$dep_export = $dependency->export();
				foreach ( $dep_export as $key => $normalized ) {
					$export[ $key ] = $normalized;
				}
			} else {
				$export[ $dependency->getRef() ] = $normalizer->normalize( $dependency );;
			}
		}

		return $export;
	}

	public function getNormalizer()
	{
		$defaultContext = [
			AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ( object $object ): string {
				return $object->getId();
			},
		];
		$normalizer     = new ObjectNormalizer( null, null, null, null, null, null, $defaultContext );
	}
}
