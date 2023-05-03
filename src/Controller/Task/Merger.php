<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class Merger extends TaskController
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
		$key    = $config['key'];
		$values = [];

		$i      = 0;
		while ( isset( $data[ $key . '__' . $i ] ) ) {
			$values[] = $data[ $key . '__' . $i ];
			unset( $data[ $key . '__' . $i ] );
		}

		$data[ $key ] = $values;

		return $data;
	}
}
