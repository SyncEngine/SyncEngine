<?php

namespace SyncEngine\Task;

use SyncEngine\Exception\InvalidException;
use SyncEngine\Model\StorageModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Data\MapData;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\Data\SchemaData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\SchemaConverter;
use SyncEngine\Task\Type\ModifierTaskType;

class Map extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = ModifierTaskType::TYPE;
		$this->icon        = 'task-map';
		$this->name        = $this->trans( 'Map' );
		$this->description = $this->trans( 'Map key value pairs' );
	}

	public function getFields(): array
	{
		return [
			'key'         => [
				'label'       => $this->trans( 'Key / Column name' ),
				'help'        => [
					$this->trans( 'The data column name for the value that needs to be mapped' ),
					$this->trans( 'Nested keys are supported: {example}', [ 'example' => 'key.nested_key', ] ),
					$this->trans( 'Leave empty for root' ),
				],
				'type'        => 'text',
				'taggable'    => true,
			],
			'action'      => [
				'label'    => $this->trans( 'Action' ),
				'type'     => 'select',
				'default'  => 'key',
				'required' => true,
				'choices'  => [
					'key'   => [
						'label'       => $this->trans( 'Map columns' ),
						'description' => $this->trans( 'Convert key names and definition schema.' ),
					],
					'value' => [
						'label'       => $this->trans( 'Map values' ),
						'description' => $this->trans( 'Convert values for a column' ),
					],
				],
			],
			'mapped_only' => [
				'label' => $this->trans( 'Only return mapped items?' ),
				'help'  => $this->trans(
					'This will also reorder your data in the order of your map.'
				),
				'type'  => 'switch',
				//'default' => false,
			],
			'remove_keys' => [
				'label'      => $this->trans( 'Remove old keys?' ),
				'type'       => 'switch',
				'conditions' => [
					'action'      => 'key',
					'mapped_only' => false,
				],
			],
			'map'         => [
				'wrap'   => false,
				'type'   => 'mapper',
				'config' => [
					'map_source'     => [
						'label'   => $this->trans( 'Map source' ),
						'type'    => 'select',
						'default' => '',
						'choices' => [
							''        => $this->trans( 'Manual' ),
							'storage' => $this->trans( 'Storage' ),
						],
					],
					'storage'        => [
						'label'      => $this->trans( 'Mapper' ),
						'type'       => 'entity',
						'entity'     => 'storage',
						'actions'    => [ 'edit', 'create' ],
						'query'      => [ 'where' => [ 'type' => 'mapper' ] ],
						'conditions' => [
							'map_source' => 'storage',
						],
					],
					'schema'         => [
						'conditions' => [
							//'action'     => 'key', // @todo Fix conditionals in React.
							'map_source' => [ 'operator' => 'empty' ],
						],
						'nested'     => [
							'' => [
								'inline' => 'fixed',
								'fields' => [
									'source' => [
										'label'   => $this->trans( 'From source schema' ),
										'type'    => 'entity',
										'entity'  => 'storage',
										'query'   => [ 'where' => [ 'type' => 'schema' ] ],
										'actions' => [ 'edit', 'create' ],
									],
									'target' => [
										'label'   => $this->trans( 'To target schema' ),
										'type'    => 'entity',
										'entity'  => 'storage',
										'query'   => [ 'where' => [ 'type' => 'schema' ] ],
										'actions' => [ 'edit', 'create' ],
									],
								],
							],
						],
					],
					'convert_schema' => [
						'label'   => $this->trans( 'Convert column types to schema where available?' ),
						'type'    => 'switch',
						'default' => true,
						'conditions' => [
							//'action' => 'key', // @todo Fix conditionals in React.
						],
					],
					'manual'         => [
						'label'      => $this->trans( 'Map' ),
						'type'       => 'mapper',
						'help'       => $this->trans( 'Nested keys are supported: {example}', [ 'example' => 'key.nested_key' ] ),
						'taggable'   => true,
						'choices'    => 'schema',
						'conditions' => [
							'map_source' => [ 'operator' => 'empty' ],
						],
					],
				],
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$mapConfig     = $config['map'];
		$mapSource     = $mapConfig['map_source'] ?? '';
		$convertSchema = $config['convert_schema'] ?? true;

		/** @var SchemaConverter $schema */
		$schema = null;
		/** @var MapData $mapper */
		$mapper = null;

		switch ( $mapSource ) {
			case 'storage':
				$storage = $mapConfig['storage']['id'] ?? $mapConfig['storage'];
				$storage = StorageModel::get( $storage );

				/** @var StorageModel $storage */
				if ( ! $storage ) {
					$context->addError( 'Map storage does not exist.' );

					return $data;
				}

				$mapper = $storage->getDataMap();

				if ( $convertSchema ) {
					$schema = $storage->getDataSchemaConverter();
				}
			break;
			default:
				if ( empty( $mapConfig['manual'] ) ) {
					$context->addLog( $this->trans( 'No manual map configured.' ) );

					return $data;
				}

				$mapper = new MapData();

				// Parse map;
				foreach ( $mapConfig['manual'] as $row ) {
					if ( ! isset( $row['source'] ) && ! isset( $row['target'] ) ) {
						continue;
					}
					$mapper->add( $row['source'], $row['target'] );
				}

				if ( $convertSchema && ! empty( $mapConfig['schema']['target'] ) ) {
					$schema = $this->getSchemaConverter(
						$mapConfig['schema']['target'],
						$mapConfig['schema']['source'] ?? null
					);
				}
			break;
		}

		$action = $config['action'] ?? 'key';
		$key    = $config['key'] ?? '';
		$item   = $data->get( $key );

		if ( empty( $item ) ) {
			$context->addLog( $this->trans( 'Cannot map empty data' ) );

			return $data;
		}

		$mapped = $item;

		switch ( $action ) {
			case 'key':

				if ( is_scalar( $item ) ) {
					$context->addError( $this->trans( 'Cannot map scalar data' ) );

					return $data;
				}

				$item = ResourceData::create( $item );

				$mapped = $item;
				if ( ! empty( $config['mapped_only'] ) ) {
					$mapped = new ResourceData( [] );
				}

				$targets = $mapper->getTargets();

				foreach ( $mapper as $source => $target ) {
					if ( empty( $target ) ) {
						// Not mapped.
						// @todo Default target source key?
						continue;
					}

					if ( isset( $item[ $source ] ) ) {

						$value = $item[ $source ];

						if ( $schema ) {
							$value = $this->convertSchema( $value, $schema, $target, $source, $context );
						}

						// No change in keys.
						if ( $source === $target ) {
							$mapped[ $source ] = $value;
							continue;
						}
						// Renamed keys.
						$mapped[ $target ] = $value;

						if ( ! empty( $config['remove_keys'] ) ) {
							// Make sure the old key isn't a new mapped key.
							if ( ! in_array( $source, $targets, true ) ) {
								unset( $mapped[ $source ] );
							}
						}
					}
				}

			break;
			case 'value':
				if ( is_iterable( $item ) ) {
					$resource = [];
					foreach ( $item as $index => $value ) {
						if ( ! isset( $mapper[ $value ] ) && ! empty( $config['mapped_only'] ) ) {
							continue;
						}
						$resource[ $index ] = $mapper->getLastTarget( (string) $value ) ?? $value;
					}
					$mapped = $resource;
				} else {
					if ( isset( $mapper[ $item ] ) ) {
						$mapped = $mapper->getLastTarget( $item );
					} elseif ( ! empty( $config['mapped_only'] ) ) {
						$mapped = null;
						// @todo How to handle single values that cannot be mapped when "mapped_only" is enabled?
					}
				}

			break;
		}

		$data->set( $mapped, $key );

		return $data;
	}

	protected function convertSchema( $value, SchemaConverter $converter, string $targetKey, string $sourceKey, ?ExecuteContext $context = null )
	{
		$config = $converter->getTarget()->getColumnConfig( $targetKey );
		if ( ! empty( $config ) ) {

			$column = $converter->getTarget()->getColumn( $targetKey );
			if ( ! $column ) {
				$context?->addLog( 'Column type not found', [ 'name' => $targetKey, 'schema' => $config ] );
				return $value;
			}

			try {
				$value = $column->format( $value, $config, $converter->getSource()?->getColumn( $sourceKey ) );
			} catch ( InvalidException $e ) {
				$context?->addLog( $e, [ 'source' => $sourceKey, 'target' => $targetKey, 'value' => $value ] );
			}
		}

		return $value;
	}

	protected function getSchemaConverter( $target, $source = null ): ?SchemaConverter
	{
		if ( $target && ! $target instanceof SchemaData ) {
			$target = is_iterable( $target ) ? SchemaData::fromDefinitions( $target ) : SchemaData::fromStorage( $target );
		}
		if ( ! $target instanceof SchemaData ) {
			return null;
		}

		if ( $source && ! $source instanceof SchemaData ) {
			$source = is_iterable( $source ) ? SchemaData::fromDefinitions( $source ) : SchemaData::fromStorage( $source );
		}

		return new SchemaConverter(
			$target,
			$source instanceof SchemaData ? $source : null
		);
	}
}
