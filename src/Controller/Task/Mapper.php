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
		];
	}

	public function execute( array $config, array $data ): array
	{
		$mapper = $config['map'];

		foreach ( $mapper as $map ) {
			if ( isset( $data[ $map['from'] ] ) ) {
				$data[ $map['to'] ] = $data[ $map['from'] ];
				unset( $data[ $map['from'] ] );
			}
		}

		return $data;
	}
}
