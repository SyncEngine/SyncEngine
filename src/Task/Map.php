<?php

namespace SyncEngine\Task;

use SyncEngine\Model\StorageModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Type\ModifierTaskType;

class Map extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = ModifierTaskType::TYPE;
		$this->name        = $this->trans( 'Map' );
		$this->description = $this->trans( 'Map key value pairs' );
	}

	public function getFields(): array
	{
		return [
			'action'      => [
				'label'    => $this->trans( 'Action' ),
				'type'     => 'select',
				'default'  => 'key',
				'required' => true,
				'choices'  => [
					'key'   => $this->trans( 'Map column names (keys)' ),
					'value' => $this->trans( 'Map values' ),
				],
			],
			'key'         => [
				'label'       => $this->trans( 'Key' ),
				'description' => $this->trans( 'The key for the value that needs to be mapped' ),
				'type'        => 'text',
				'taggable'    => true,
				'conditions'  => [
					'action' => 'value',
				],
			],
			'mapped_only' => [
				'label'   => $this->trans( 'Only return mapped items?' ),
				'type'    => 'boolean',
				//'default' => false,
			],
			'remove_keys' => [
				'label'      => $this->trans( 'Remove old keys?' ),
				'type'       => 'boolean',
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
					'field_storages' => [
						'conditions' => [
							'map_source' => [ 'operator' => 'empty' ],
						],
						'nested'     => [
							'' => [
								'inline' => 'fixed',
								'fields' => [
									'source' => [
										'label'   => $this->trans( 'Source Schema' ),
										'type'    => 'entity',
										'entity'  => 'storage',
										'query'   => [ 'where' => [ 'type' => 'schema' ] ],
										'actions' => [ 'edit', 'create' ],
									],
									'target' => [
										'label'   => $this->trans( 'Target Schema' ),
										'type'    => 'entity',
										'entity'  => 'storage',
										'query'   => [ 'where' => [ 'type' => 'schema' ] ],
										'actions' => [ 'edit', 'create' ],
									],
								],
							],
						],
					],
					'manual'         => [
						'label'      => $this->trans( 'Map' ),
						'type'       => 'mapper',
						'help'       => $this->trans( 'Nested keys are supported: key.nested_key' ),
						'taggable'   => true,
						'choices'    => 'field_storages',
						'conditions' => [
							'map_source' => [ 'operator' => 'empty' ],
						],
					],
				],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$mapConfig = $config['map'];
		$mapSource = $mapConfig['map_source'] ?? '';
		$mapper    = [];

		switch ( $mapSource ) {
			case 'storage':
				$storage = $mapConfig['storage']['id'] ?? $mapConfig['storage'];
				$storage = StorageModel::get( $storage );

				$mapper = $storage->getDataMap();
			break;
			default:
				// Parse map;
				foreach ( $mapConfig['manual'] as $row ) {
					if ( ! isset( $row['source'] ) && ! isset( $row['target'] ) ) {
						continue;
					}
					$mapper[ $row['source'] ] = $row['target'];
				}
			break;
		}

		$action = $config['action'] ?? 'key';
		$key    = $config['key'] ?? '';

		$mapped = $data;
		if ( ! empty( $config['mapped_only'] ) ) {
			$mapped = new ExecuteData( [] );
		}

		switch ( $action ) {
			case 'key':

				foreach ( $mapper as $source => $target ) {
					if ( empty( $target ) ) {
						// Not mapped.
						// @todo Default target source key?
						continue;
					}

					if ( isset( $data[ $source ] ) ) {

						// No change in keys.
						if ( $source === $target ) {
							$mapped[ $source ] = $data[ $source ];
							continue;
						}
						// Renamed keys.
						$mapped[ $target ] = $data[ $source ];

						if ( ! empty( $config['remove_keys'] ) ) {
							// Make sure the old key isn't a new mapped key.
							if ( ! in_array( $source, $mapper, true ) ) {
								unset( $mapped[ $source ] );
							}
						}
					}
				}

			break;
			case 'value':

				if ( ! $key || empty( $data[ $key ] ) ) {
					return $data;
				}

				if ( is_iterable( $data[ $key ] ) ) {
					$resource = $mapped->get( $key ) ?? [];
					foreach ( $data[ $key ] as $index => $value ) {
						if ( ! isset( $mapper[ $value ] ) && ! empty( $config['mapped_only'] ) ) {
							continue;
						}
						$resource[ $index ] = $mapper[ $value ] ?? $value;
					}
					$mapped->set( $resource, $key );
				} elseif ( isset( $mapper[ $data[ $key ] ] ) ) {
					$mapped[ $key ] = $mapper[ $data[ $key ] ];
				}

			break;
		}

		return $mapped;
	}
}
