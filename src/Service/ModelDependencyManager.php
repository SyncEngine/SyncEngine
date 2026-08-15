<?php

namespace SyncEngine\Service;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\Interface\Persistable;
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
	 * Resolve all dependencies for a model (unlimited chain following).
	 */
	public function getDependencies(
		EngineModel $model,
		$recurse = true,
	): array
	{
		$ref = $model->getRef();

		if ( ! $ref ) {
			return [];
		}

		// Return from cache if available.
		if ( isset( $this->dependencyCache[ $ref ] ) ) {
			return $this->dependencyCache[ $ref ];
		}

		$dependencies = $this->getConfigDependencies( $model->getConfig(), $model->getFields(), [], $recurse );

		$this->dependencyCache[ $ref ] = $dependencies;

		return $dependencies;
	}

	/**
	 * Scan config fields to find entity dependencies.
	 *
	 * @param  ConfigData|array  $config
	 * @param  FieldCollection|array  $fields
	 * @param  array  $stack  Cycle detection path (refs of models currently being resolved).
	 * @param  bool  $recurse  Follow resolved entities into their own configs.
	 */
	public function getConfigDependencies(
		ConfigData|array $config = [],
		FieldCollection|array $fields = [],
		array $stack = [],
		bool $recurse = true,
	): array
	{
		if ( ! $config || ! $fields ) {
			return $stack;
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
							$stack = $this->getEntityDependency( $entity, $value, $stack, $recurse );
						}
					break;

					case 'entities':
						$entity = $field['entity'] ?? '';
						if ( $entity ) {
							foreach ( $value as $id ) {
								$stack = $this->getEntityDependency( $entity, $id, $stack, $recurse );
							}
						}
					break;

					case 'flow':
					case 'sequence':
						$entity = $field['entity'] ?? '';
						if ( $entity ) {
							foreach ( $value as $step ) {
								$id = null;
								if ( is_array( $step ) ) {
									$id = $step['id'] ?? $step[ $entity ] ?? null;
								} elseif ( is_numeric( $step ) ) {
									$id = $step;
								} else {
									continue;
								}

								if ( $id ) {
									$stack = $this->getEntityDependency( $entity, $id, $stack, $recurse );
								}

								$stepConfig = $step['config'] ?? [];
								if ( $stepConfig && 'routine' === $entity ) {
									$routine = RoutineModel::get( $id );
									if ( $routine ) {
										if ( isset( $stepConfig['input'] ) ) {
											$stack = $this->getConfigDependencies(
												$stepConfig['input'],
												$routine->getInputSchema()->getFields(),
												$stack,
												$recurse
											);
										}
										if ( isset( $stepConfig['variables'] ) ) {
											$stack = $this->getConfigDependencies(
												$stepConfig['variables'],
												$routine->getVariableSchema()->getFields(),
												$stack,
												$recurse
											);
										}
									}
								}
							}
						}
					break;

					case 'tasks':
						foreach ( $value as $taskConfig ) {
							$taskModel = TaskModel::get( $taskConfig['_class'] );
							if ( $taskModel ) {
								$stack = $this->getConfigDependencies( $taskConfig, $taskModel->getFields(), $stack, $recurse );
							}
						}
					break;

					case 'webservice':
						$webserviceModel = WebserviceModel::get( $value['_class'] );
						if ( $webserviceModel ) {
							$stack = $this->getConfigDependencies( $config[ $name ], $webserviceModel->getFields(), $stack, $recurse );
						}
					break;

					case 'repeater':
						foreach ( $value as $repeaterConfig ) {
							$stack = $this->getConfigDependencies( $repeaterConfig, $field['fieldset'] ?? [], $stack, $recurse );
						}
						unset( $field['fieldset'] );
					break;

					case 'schema':
						if ( is_array( $value ) ) {
							$stack = $this->getConfigSchemaDependencies( $value, $stack );
						}
					break;

					case 'mapper':
						$mapFields = $field['config'] ?? [];
						if ( is_array( $value ) && is_array( $mapFields ) ) {
							$stack = $this->getConfigDependencies( $value, $mapFields, $stack, $recurse );
						}
					break;
				}
			}

			if ( ! empty( $field['nested'] ) ) {
				$stack = $this->getConfigDependencies( (array) $value, $field['nested'], $stack, $recurse );
				unset( $field['nested'] );
			}

			$stack = $this->getConfigDependencies( $config, $field, $stack, $recurse );
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
				if ( ! isset( $stack[ $key ] ) ) {
					$stack[ $key ] = $storageModel;
					if ( $recurse ) {
						$stack = $storageModel->getConfigDependencies( $recurse, $stack );
					}
				}
			}
		}

		ksort( $stack );

		return $stack;
	}

	/**
	 * Resolve a single entity reference to an EntityModel.
	 *
	 * @param  string  $entity
	 * @param  mixed  $id
	 * @param  array  $stack  Cycle detection path (refs of models currently being resolved).
	 * @param  bool  $recurse  Follow this entity's config into its own dependencies.
	 */
	public function getEntityDependency(
		string $entity,
		mixed $id,
		array $stack = [],
		bool $recurse = true,
	): array
	{
		$model = EntityModel::get( $id, $entity );
		$entityKey   = strtolower( $entity );

		if ( ! $model ) {
			return $stack;
		}

		// getId() is delegated via __call on the Doctrine entity, so check the entity instead.
		if ( ! $model instanceof Persistable ) {
			return $stack;
		}

		// Cycle detection: if we're already resolving this model, stop.
		$depKey = $entityKey . ':' . $model->getId();
		if ( isset( $stack[ $depKey ] ) ) {
			return $stack;
		}

		$stack[ $depKey ] = $model;

		// Only follow into sub-models if explicitly requested.
		if ( $recurse && method_exists( $model, 'getConfigDependencies' ) ) {
			$stack = $model->getConfigDependencies( $recurse, $stack );
		}

		return $stack;
	}

	/**
	 * Extract dependencies from schema config (storage references).
	 *
	 * @param  array  $config
	 * @param  array  $stack  Cycle detection path.
	 */
	public function getConfigSchemaDependencies(
		array $config = [],
		array $stack = [],
	): array
	{
		if ( ! empty( $config['storage'] ) && is_numeric( $config['storage'] ) && 'storage' === ( $config['source'] ?? '' ) ) {
			$stack = $this->getEntityDependency( 'storage', $config['storage'], $stack );
		} else {
			foreach ( $config as $value ) {
				if ( is_array( $value ) ) {
					$stack = $this->getConfigSchemaDependencies( $value, $stack );
				}
			}
		}

		return $stack;
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

		$ref = is_callable( [ $entityModel, 'getRef' ] ) ? $entityModel->getRef() : null;
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

		/**
		 * @var class-string{AutomationModel|FlowModel|RoutineModel|StorageModel} $configModel
		 */
		foreach ( $configModels as $name => $configModel ) {
			$results = $configModel::getAll( [
				'search' => [
					'config' => '"' . strtolower( $modelClass ) . ':' . $id . '"',
				],
			] );

			if ( $results ) {
				foreach ( $results as $dependent ) {
					$depRef = is_callable( [ $dependent, 'getRef' ] ) ? $dependent->getRef() : null;
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
		$this->dependencyCache = [];
		$this->dependentCache  = [];
	}
}
