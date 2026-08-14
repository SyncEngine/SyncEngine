<?php

namespace SyncEngine\Service;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\StorageModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\Tag\TagExtractor;
use SyncEngine\Structure\Data\ConfigData;

class ModelDependencyManager
{
	/**
	 * Cached dependencies keyed by model ref.
	 */
	private array $dependencyCache = [];

	/**
	 * Cached dependents keyed by "entity:id".
	 */
	private array $dependentCache = [];

	/**
	 * Track the current resolution path for cycle detection.
	 */
	private array $resolutionStack = [];

	/**
	 * Resolve all dependencies for a model (with caching and cycle detection).
	 */
	public function getDependencies(
		EngineModel $model,
		bool $recursive = true,
	): array
	{
		$ref = $model->getRef();

		if ( ! $ref ) {
			return [];
		}

		// Cycle detection.
		if ( in_array( $ref, $this->resolutionStack, true ) ) {
			return [];
		}

		// Return from cache if available.
		if ( isset( $this->dependencyCache[ $ref ] ) ) {
			return $this->dependencyCache[ $ref ];
		}

		$this->resolutionStack[] = $ref;

		$config = $model->getConfig();
		$fields = $model->getFields();

		$dependencies = $this->getConfigDependencies( $config, $fields, $recursive );

		$this->dependencyCache[ $ref ] = $dependencies;
		array_pop( $this->resolutionStack );

		return $dependencies;
	}

	/**
	 * Scan config fields to find entity dependencies. No caching applied.
	 */
	public function getConfigDependencies(
		ConfigData|array $config = [],
		FieldCollection|array $fields = [],
		array|bool $recursive = false,
	): array
	{
		$dependencies = [];
		if ( $recursive ) {
			$dependencies = is_array( $recursive ) ? $recursive : [];
		}

		if ( ! $config || ! $fields ) {
			return $dependencies;
		}

		$fields = $fields instanceof FieldCollection ? $fields->normalize() : $fields;

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$name  = $field['name'] ?? $key;
			$value = $config[ $name ] ?? null;

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
							}
						}
					break;

					case 'webservice':
						$webserviceModel = WebserviceModel::get( $value['_class'] );
						if ( $webserviceModel ) {
							$dependencies = $this->getConfigDependencies( $config[ $name ], $webserviceModel->getFields(), $dependencies );
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

			$dependencies = $this->getConfigDependencies( $config, $field, $dependencies );
		}

		// Extract {{ storage.* }} tags from config values.
		$tagExtractor = new TagExtractor();
		$tags = $tagExtractor->extractTags( $config, 'storage' );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$refPart = $tagExtractor->getTagPart( $tag, 1 );
				if ( ! $refPart ) {
					continue;
				}

				$storageModel = StorageModel::get( $refPart );
				if ( ! $storageModel ) {
					continue;
				}

				$key = 'storage:' . $storageModel->getId();
				if ( ! isset( $dependencies[ $key ] ) ) {
					$dependencies[ $key ] = $storageModel;
					if ( $recursive ) {
						$dependencies = $storageModel->getConfigDependencies( $dependencies );
					}
				}
			}
		}

		ksort( $dependencies );

		return $dependencies;
	}

	/**
	 * Resolve a single entity reference to an EntityModel.
	 */
	public function getEntityDependency(
		string $entity,
		mixed $id,
		array|bool $recursive = [],
	): array
	{
		$dependencies = [];
		if ( $recursive ) {
			$dependencies = is_array( $recursive ) ? $recursive : [];
		}

		$entityModel = EntityModel::get( $id, $entity );
		$entityKey   = strtolower( $entity );

		if ( ! $entityModel ) {
			return $dependencies;
		}

		// getId() is delegated via __call on the Doctrine entity, so check the entity instead.
		$doctrineEntity = $entityModel->getEntity();
		if ( ! method_exists( $doctrineEntity, 'getId' ) ) {
			return $dependencies;
		}

		$depKey = $entityKey . ':' . $doctrineEntity->getId();
		if ( isset( $dependencies[ $depKey ] ) ) {
			return $dependencies;
		}

		$dependencies[ $depKey ] = $entityModel;

		if ( $recursive && method_exists( $entityModel, 'getConfigDependencies' ) ) {
			$dependencies = $entityModel->getConfigDependencies( $dependencies );
		}

		return $dependencies;
	}

	/**
	 * Extract dependencies from schema config (storage references).
	 */
	public function getConfigSchemaDependencies(
		array $config = [],
		array|bool $recursive = false,
	): array
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

	/**
	 * Reverse lookup: find all models that reference a given entity.
	 */
	public function getDependents(
		EntityModel $entityModel,
	): array
	{
		$id = $entityModel->getId();
		if ( ! $id ) {
			return [];
		}

		$ref = method_exists( $entityModel, 'getRef' ) ? $entityModel->getRef() : null;
		if ( ! $ref ) {
			$ref = strtolower( EntityModel::getEntityReflection( $entityModel->getEntity() )->getShortName() ) . ':' . $id;
		}

		if ( isset( $this->dependentCache[ $ref ] ) ) {
			return $this->dependentCache[ $ref ];
		}

		$dependents = [];

		$modelClass = EntityModel::getEntityReflection( $entityModel->getEntity() )->getShortName();

		$configModels = [
			'automation' => AutomationModel::class,
			'flow'       => FlowModel::class,
			'routine'    => RoutineModel::class,
			'storage'    => StorageModel::class,
		];

		foreach ( $configModels as $name => $configModel ) {
			$results = $configModel::getAll( [
				'search' => [
					'config' => '"' . strtolower( $modelClass ) . ':' . $id . '"',
				],
			] );

			if ( $results ) {
				foreach ( $results as $dependent ) {
					$depRef = method_exists( $dependent, 'getRef' ) ? $dependent->getRef() : null;
					if ( ! $depRef ) {
						continue;
					}
					if ( ! isset( $this->dependentCache[ $depRef ] ) ) {
						$this->dependentCache[ $depRef ] = $dependent;
					}
					$dependents[] = $this->dependentCache[ $depRef ];
				}
			}
		}

		return $dependents;
	}

	/**
	 * Gate check: does this model have dependencies?
	 */
	public function hasDependencies(
		EngineModel $model,
	): bool
	{
		return ! empty( $this->getDependencies( $model ) );
	}

	/**
	 * Gate check: are there models depending on this entity?
	 */
	public function hasDependents(
		EntityModel $entityModel,
	): bool
	{
		return ! empty( $this->getDependents( $entityModel ) );
	}

	/**
	 * Clear all caches. Useful between test runs or long-lived instances.
	 */
	public function clearCache(): void
	{
		$this->dependencyCache    = [];
		$this->dependentCache     = [];
		$this->resolutionStack    = [];
	}
}
