<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class Splitter extends TaskController
{
	public function getFields(): array {
		return [
			'key' => [
				'type' => 'text', // @todo Column/Key slection field type.
			],
			'separator' => [
				'type' => 'separator',
			],
		];
	}

	public function execute( array $config, array $data ): array
	{
		$key   = $config['key'];
		$field = $data[ $key ];

		$split = explode( $config['separator'], $field );

		for ( $i = 0; $i < count( $split ); $i++ ) {
			$data[ $key . '__' . $i ] = $split[ $i ];
		}

		return $data;
	}
}
