<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class Merger extends TaskController
{
	public $type = 'merger';
	public $name = 'Merger';
	public $description = 'Merge columns into single column';

	public function getFields(): array {
		return [
			'key' => [
				'type' => 'text', // @todo Column/Key slection field type.
			],
			'separator' => [
				'type' => 'separator',
			],
			'postfix' => [
				'type' => 'text',
				'default' => '__',
			],
		];
	}

	public function execute( array $config, array $data ): array
	{
		$key    = $config['key'];
		$values = [];

		$i      = 0;
		while ( isset( $data[ $key . $config['postfix'] . $i ] ) ) {
			$values[] = $data[ $key . $config['postfix'] . $i ];
			unset( $data[ $key . $config['postfix'] . $i ] );
		}

		$data[ $key ] = implode( $config['separator'], $values );

		return $data;
	}
}
