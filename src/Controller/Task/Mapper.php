<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class Mapper extends TaskController
{
	public $name = 'mapper';
	public $label = 'Mapper';
	public $description = 'Map key value pairs';

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
		$mapper = $config['map'];

		$mapped = $data;
		if ( ! empty( $config['mapped_only'] ) ) {
			$mapped = [];
		}

		foreach ( $mapper as $map ) {

			// Not mapped.
			if ( empty( $map['to'] ) ) {
				// @todo Default to from key?
				continue;
			}

			if ( isset( $data[ $map['from'] ] ) ) {

				// No change in keys.
				if ( $map['from'] === $map['to'] ) {
					$mapped[ $map['from'] ] = $data[ $map['from'] ];
					continue;
				}
				// Renamed keys.
				$mapped[ $map['to'] ] = $data[ $map['from'] ];

				// @todo Removal protection when new keys overlap?
				unset( $mapped[ $map['from'] ] );
			}
		}

		return $mapped;
	}
}
