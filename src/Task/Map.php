<?php

namespace SyncEngine\Task;

use SyncEngine\Model\ColumnModel;
use SyncEngine\Model\StorageModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Data\MapData;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
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

		$schema = [];
		$mapper = [];

		switch ( $mapSource ) {
			case 'storage':
				$storage = $mapConfig['storage']['id'] ?? $mapConfig['storage'];
				$storage = StorageModel::get( $storage );

				if ( ! $storage ) {
					$context->addError( 'Map storage does not exist.' );

					return $data;
				}

				$mapper = $storage->getDataMap();

				if ( $convertSchema ) {
					$schema = $storage->getDataSchema();
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

				if ( $convertSchema ) {
					$schema = $mapConfig['schema'] ?? [];

					if ( ! empty( $schema['target'] ) && ! is_array( $schema['target'] ) ) {
						$schema['target'] = StorageModel::get( $schema['target'] )?->getDataSchema() ?? [];

						if ( ! empty( $schema['source'] ) && ! is_array( $schema['source'] ) ) {
							$schema['source'] = StorageModel::get( $schema['source'] )?->getDataSchema() ?? [];
						}
					}
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

						if ( ! empty( $schema['target'][ $target ] ) ) {
							$value = $this->convertSchema(
								$value,
								$schema['target'][ $target ],
								$schema['source'][ $source ] ?? [],
								$context
							);
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

	public function convertSchema( $value, array $targetSchema, array $sourceSchema = [], ?ExecuteContext $context = null ): mixed
	{
		$targetColumn = ColumnModel::get( $targetSchema['_class'] ?? '' );

		if ( ! $targetColumn ) {
			$context?->addLog( 'Column type not found', [ 'schema' => $targetSchema ] );
			return $value;
		}

		$targetColumn->setConfig( $targetSchema );

		if ( $sourceSchema ) {
			$sourceColumn = ColumnModel::get( $sourceSchema['_class'] );
			$sourceColumn->setConfig( $sourceSchema );
		}

		return $targetColumn->format( $value, $targetSchema, $sourceColumn ?? null );
	}
}
