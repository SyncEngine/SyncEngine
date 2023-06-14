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
	}

	public function getFields(): array {
		return [
			'map' => [
				'type' => 'mapper'
			],
			'mapped_only' => [
				'type' => 'boolean',
			],
		];
	}

	public function execute( array $config, array $data ): array
	{
		$mapper = array_filter( $config['map'] );

		$mapped = $data;
		if ( ! empty( $config['mapped_only'] ) ) {
			$mapped = [];
		}

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

		return $mapped;
	}
}
