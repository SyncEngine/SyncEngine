<?php

namespace SyncEngine\Service;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Normalizable;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Interface\Taggable;

class ModelNormalizer
{
	public function __construct(
		private ?ModelDependencyManager $dependencyManager = null,
	) {
		// Allow not passing a dependency manager for testing purposes or non-Model normalization.
		if ( ! $this->dependencyManager ) {
			$this->dependencyManager = new ModelDependencyManager();
		}
	}

	private ?Serializer $serializer = null;
	private static ?string $runningRef = null;
	private static array $normalized = [];

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
					$valueRef = new \ReflectionClass( $value );
					if ( $valueRef->isEnum() ) {
						$value = $value->value;
					} else {
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

	public function getDependents( $model ): array
	{
		$dependents = [];

		// Delegate raw lookup to ModelDependencyManager.
		$rawDependents = $this->dependencyManager->getDependents( $model );

		foreach ( $rawDependents as $dependent ) {
			$ref = $dependent->getRef();
			if ( ! isset( static::$normalized[ $ref ] ) ) {
				static::$normalized[ $ref ] = $dependent->normalize( false, false );
			}
			$dependents[] = static::$normalized[ $ref ];
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
