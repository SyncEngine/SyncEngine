<?php

namespace SyncEngine\Service;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Normalizable;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Structure\Data\ResourceData;

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

	public function normalize( $model, $includeDependencies = false, $includeDependents = false ): array
	{
		if ( ! $model instanceof EntityModel ) {
			// Other.
			return (array) $this->getSerializer()->normalize( $model );
		}

		$currentRef = ( method_exists( $model, 'getRef' ) ) ? $model->getRef() : '_';

		if ( $currentRef === self::$runningRef ) {
			return [];
		}

		// Should not be possible but verify anyway.
		if ( ! empty( static::$normalized[ $currentRef ] ) ) {
			return static::$normalized[ $currentRef ];
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

			$modelRef = new \ReflectionClass( $model );
			$useModel = $modelRef->hasMethod( $getter ) && match( $name ) {
					'supervisor' => $includeDependencies,
					default => is_callable( [ $model, $getter ] ),
				};

			if ( $useModel ) {
				if ( ! $classRef->hasMethod( $getter ) ) {
					continue;
				}
				if ( $classRef->getMethod( $getter )?->getAttributes( Ignore::class, \ReflectionAttribute::IS_INSTANCEOF ) ) {
					continue;
				}
				if ( $modelRef->getMethod( $getter )?->getAttributes( Ignore::class, \ReflectionAttribute::IS_INSTANCEOF ) ) {
					continue;
				}

				// Call Model method instead of entity to allow context overrides.
				$value = call_user_func( [ $model, $getter ] );
			} else {
				$value = $propertyAccess->getValue( $entity, $name );
			}

			if ( $value instanceof \DateTimeInterface ) {
				$value = $value->getTimestamp();
			}

			if ( is_object( $value ) ) {
				$valueRef = new \ReflectionClass( $value );
				if ( $valueRef->isEnum() ) {
					$value = $value->value;
				} elseif ( $valueRef->isCloneable() ) {
					// Remove ref.
					$value = clone $value;
				} elseif ( $value instanceof ResourceData ) {
					$value = $value->normalize();
				}
			}

			if ( is_iterable( $value ) ) {
				$value = ResourceData::create( $value )->normalize();
			} elseif ( $value instanceof Normalizable ) {
				if ( $value instanceof EntityModel ) {
					if ( $includeDependencies ) {
						$value = $value->normalize( $includeDependencies, $includeDependents );
					} else {
						$value = $value->getId();
					}
				} else {
					$value = $value->normalize();
				}
			}

			$data[ $name ] = $value;
		}

		if ( $model instanceof Taggable ) {
			$data['tags'] = $model->getTags();
		}

		if ( $includeDependencies && method_exists( $model, 'getConfigDependencies' ) ) {
			$data['_dependencies'] = $this->getDependencies( $model );
		}

		if ( $includeDependents ) {
			$data['_dependents'] = $this->getDependents( $model );
		}

		$this->reset( $currentRef );

		return $this->getSerializer()->normalize( $data );
	}

	public function getDependencies( $model ): array
	{
		if ( method_exists( $model, 'getConfigDependencies' ) ) {
			$dependencies = $model->getConfigDependencies();
		} else {
			$dependencies = $this->dependencyManager->getDependencies( $model, false );
		}

		/** @var EngineModel $dependency */
		foreach ( $dependencies as $key => $dependency ) {
			$ref = $dependency->getRef();
			if ( ! isset( static::$normalized[ $ref ] ) ) {
				static::$normalized[ $ref ] = $dependency->normalize( false, false );
			}
			$dependencies[ $key ] = static::$normalized[ $ref ];
		}

		return $dependencies;
	}

	public function getDependents( $model ): array
	{
		// Delegate raw lookup to ModelDependencyManager.
		$dependents = $this->dependencyManager->getDependents( $model );

		/** @var EngineModel $dependent */
		foreach ( $dependents as $key => $dependent ) {
			$ref = $dependent->getRef();
			if ( ! isset( static::$normalized[ $ref ] ) ) {
				static::$normalized[ $ref ] = $dependent->normalize( false, false );
			}
			$dependents[ $key ] = static::$normalized[ $ref ];
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
				try {
					return $object->getId();
				} catch ( \Throwable $e ) {
					return spl_object_hash( $object );
				}
			},
		];

		return new ObjectNormalizer( null, null, null, null, null, null, $defaultContext );
	}
}
