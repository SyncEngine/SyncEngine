<?php

namespace App\Task;

use App\Model\DatasetModel;
use App\Model\TaskModel;
use App\Service\ExecutionContext;

class Map extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'modifier';
		$this->name        = 'Map';
		$this->description = 'Map key value pairs';

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'action'      => [
				'label'   => 'Action',
				'type'    => 'select',
				'default' => 'key',
				'choices' => [
					'key'   => 'Map keys',
					'value' => 'Map values',
				],
			],
			'key'         => [
				'label'        => 'Key',
				'description'  => 'The key for the value that needs to be mapped',
				'type'         => 'text',
				'conditionals' => [
					'action' => 'value',
				],
			],
			'remove_keys' => [
				'label'        => 'Remove old keys?',
				'type'         => 'boolean',
				'conditionals' => [
					'action' => 'key',
				],
			],
			'mapped_only' => [
				'label' => 'Only return mapped items?',
				'type'  => 'boolean',
			],
			'map_source'  => [
				'label'   => 'Map source',
				'type'    => 'select',
				'default' => '',
				'choices' => [
					''        => 'Manual',
					'dataset' => 'Dataset',
				],
			],
			'dataset'     => [
				'label'        => 'Dataset',
				'type'         => 'entity',
				'entity'       => 'dataset',
				'actions'      => [ 'edit', 'create' ],
				//'choices'      => $flows,
				'conditionals' => [
					'map_source' => 'dataset',
				],
			],
			'map'         => [
				'label'        => 'Map',
				'type'         => 'mapper',
				'conditionals' => [
					'map_source' => [ 'operator' => 'empty' ],
				],
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		$map_source = $config['map_source'] ?? '';
		$mapper     = [];

		switch ( $map_source ) {
			case 'dataset':
				$dataset = $config['dataset']['id'] ?? $config['dataset'];
				$dataset = DatasetModel::get( $dataset );

				$mapper = $dataset->getDataAsMap();
			break;
			default:
				// Parse map;
				foreach ( $config['map'] as $map ) {
					if ( ! isset( $map['source'] ) && ! isset( $map['target'] ) ) {
						continue;
					}
					$mapper[ $map['source'] ] = $map['target'];
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
