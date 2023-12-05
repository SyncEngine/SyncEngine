<?php

namespace SyncEngine\Task;

use SyncEngine\Model\DatasetModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\ResourceData;

class Map extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'modifier';
		$this->name        = $this->trans( 'Map' );
		$this->description = $this->trans( 'Map key value pairs' );

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'action'      => [
				'label'   => $this->trans( 'Action' ),
				'type'    => 'select',
				'default' => 'key',
				'choices' => [
					'key'   => $this->trans( 'Map keys' ),
					'value' => $this->trans( 'Map values' ),
				],
			],
			'key'         => [
				'label'        => $this->trans( 'Key' ),
				'description'  => $this->trans( 'The key for the value that needs to be mapped' ),
				'type'         => 'text',
				'taggable'     => true,
				'conditionals' => [
					'action' => 'value',
				],
			],
			'mapped_only' => [
				'label'   => $this->trans( 'Only return mapped items?' ),
				'type'    => 'boolean',
				//'default' => false,
			],
			'remove_keys' => [
				'label'        => $this->trans( 'Remove old keys?' ),
				'type'         => 'boolean',
				'conditionals' => [
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
							'dataset' => $this->trans( 'Dataset' ),
						],
					],
					'dataset'        => [
						'label'        => $this->trans( 'Dataset' ),
						'type'         => 'entity',
						'entity'       => 'dataset',
						'actions'      => [ 'edit', 'create' ],
						'query'        => [ 'where' => [ 'type' => 'mapper' ] ],
						'conditionals' => [
							'map_source' => 'dataset',
						],
					],
					'field_datasets' => [
						'conditionals' => [
							'map_source' => [ 'operator' => 'empty' ],
						],
						'nested'       => [
							'' => [
								'inline' => 'columns',
								'fields' => [
									'source' => [
										'label'   => $this->trans( 'From Fields Dataset' ),
										'type'    => 'entity',
										'entity'  => 'dataset',
										'query'   => [ 'where' => [ 'type' => 'fields' ] ],
										'actions' => [ 'edit', 'create' ],
									],
									'target' => [
										'label'   => $this->trans( 'To Fields Dataset' ),
										'type'    => 'entity',
										'entity'  => 'dataset',
										'query'   => [ 'where' => [ 'type' => 'fields' ] ],
										'actions' => [ 'edit', 'create' ],
									],
								],
							],
						],
					],
					'manual'         => [
						'label'        => $this->trans( 'Map' ),
						'type'         => 'mapper',
						'help'         => $this->trans( 'Nested keys are supported: key.nested_key' ),
						'taggable'     => true,
						'choices'      => 'field_datasets',
						'conditionals' => [
							'map_source' => [ 'operator' => 'empty' ],
						],
					],
				],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, array $data ): array
	{
		$mapConfig = $config['map'];
		$mapSource = $mapConfig['map_source'] ?? '';
		$mapper    = [];

		switch ( $mapSource ) {
			case 'dataset':
				$dataset = $mapConfig['dataset']['id'] ?? $mapConfig['dataset'];
				$dataset = DatasetModel::get( $dataset );

				$mapper = $dataset->getDataMap();
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
			$mapped = [];
		}

		switch ( $action ) {
			case 'key':
				$mapped   = new ResourceData( $mapped ?? [] );
				$resource = new ResourceData( $data ?? [] );

				// @todo support key?

				foreach ( $mapper as $source => $target ) {
					if ( empty( $target ) ) {
						// Not mapped.
						// @todo Default target source key?
						continue;
					}

					if ( isset( $resource[ $source ] ) ) {

						// No change in keys.
						if ( $source === $target ) {
							$mapped[ $source ] = $resource[ $source ];
							continue;
						}
						// Renamed keys.
						$mapped[ $target ] = $resource[ $source ];

						if ( ! empty( $config['remove_keys'] ) ) {
							// Make sure the old key isn't a new mapped key.
							if ( ! in_array( $source, $mapper, true ) ) {
								unset( $mapped[ $source ] );
							}
						}
					}
				}

				$mapped = $mapped->get();
			break;
			case 'value':

				if ( ! $key || empty( $data[ $key ] ) ) {
					return $data;
				}

				if ( is_array( $data[ $key ] ) ) {
					foreach ( $data[ $key ] as $index => $value ) {
						if ( ! isset( $mapper[ $value ] ) && ! empty( $config['mapped_only'] ) ) {
							continue;
						}
						$mapped[ $key ][ $index ] = $mapper[ $value ] ?? $value;
					}
				} elseif ( isset( $mapper[ $data[ $key ] ] ) ) {
					$mapped[ $key ] = $mapper[ $data[ $key ] ];
				}

			break;
		}

		return $mapped;
	}
}
