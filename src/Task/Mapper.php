<?php

namespace App\Task;

use App\Model\TaskModel;

class Mapper extends TaskModel
{
	public function __construct()
	{
		$this->type = 'mapper';
		$this->name = 'Mapper';
		$this->description = 'Map key value pairs';

		parent::__construct();
	}

	public function getFields(): array {
		return [
			'action' => [
				'label'   => 'Action',
				'type'    => 'select',
				'default' => 'key',
				'choices' => [
					'key'   => 'Map keys',
					'value' => 'Map values',
				],
			],
			'key' => [
				'label' => 'Key',
				'description' => 'The key for the value that needs to be mapped',
				'type' => 'text',
				'conditionals' => [
					'action' => 'value',
				],
			],
			'mapped_only' => [
				'label' => 'Only return mapped items?',
				'type' => 'boolean',
			],
			'remove' => [
				'label' => 'Remove old keys?',
				'type' => 'boolean',
				'conditionals' => [
					'action' => 'key',
				],
			],
			'map' => [
				'label' => 'Map',
				'type' => 'mapper',
			],
		];
	}

	public function execute( array $config, array $data ): array
	{
		$mapper = array_filter( $config['map'] );
		$action = $config['action'] ?? 'key';
		$key    = $config['key'] ?? '';

		$mapped = $data;
		if ( ! empty( $config['mapped_only'] ) ) {
			$mapped = [];
		}

		switch ( $action ) {
			case 'key':
				foreach ( $mapper as $map ) {
					if ( empty( $map['target'] ) ) {
						// Not mapped.
						// @todo Default target source key?
						continue;
					}

					if ( isset( $data[ $map['source'] ] ) ) {

						// No change in keys.
						if ( $map['source'] === $map['target'] ) {
							$mapped[ $map['source'] ] = $data[ $map['source'] ];
							continue;
						}
						// Renamed keys.
						$mapped[ $map['target'] ] = $data[ $map['source'] ];

						if ( ! empty( $config['remove'] ) ) {
							// @todo Removal protection when new keys overlap?
							unset( $mapped[ $map['source'] ] );
						}
					}
				}
			break;
			case 'value':
				$mapper = array_combine(
					array_map( function( $row ) { return $row['source'] ?? ''; }, $mapper ),
					array_map( function( $row ) { return $row['target'] ?? ''; }, $mapper ),
				);

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
