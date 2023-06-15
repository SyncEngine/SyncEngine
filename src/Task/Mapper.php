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
				'description' => 'Optional: Map a key value instead of the root value',
				'type' => 'text',
				'conditionals' => [
					'action' => 'value',
				],
			],
			'map' => [
				'label' => 'Map',
				'type' => 'mapper',
			],
			'mapped_only' => [
				'label' => 'Only return mapped items?',
				'type' => 'boolean',
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
					// Not mapped.
					if ( empty( $map['target'] ) ) {
						// @targetdo Default target source key?
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

						// @todo Removal protection when new keys overlap?
						unset( $mapped[ $map['source'] ] );
					}
				}
			break;
			case 'value':

				if ( ! $key ) {
					return $data;
				}

				$mapper = array_combine(
					array_map( function( $row ) { return $row['source'] ?? ''; }, $mapper ),
					array_map( function( $row ) { return $row['target'] ?? ''; }, $mapper ),
				);

				foreach ( $data as $index => $row ) {
					if ( isset( $mapper[ $row ] ) ) {
						$mapped[ $index ] = $mapper[ $row ];
					}
				}

			break;
		}

		return $mapped;
	}
}
