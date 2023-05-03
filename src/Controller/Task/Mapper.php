<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class Mapper extends TaskController
{
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
			if ( empty( $map['to'] ) ) {
				// @todo Default to from key?
				continue;
			}
			if ( $map['from'] === $map['to'] ) {
				$mapped[ $map['from'] ] = $data[ $map['from'] ];
				continue;
			}
			if ( isset( $data[ $map['from'] ] ) ) {
				$mapped[ $map['to'] ] = $data[ $map['from'] ];
				// @todo Removal protection when new keys overlap?
				unset( $mapped[ $map['from'] ] );
			}
		}

		return $mapped;
	}
}
