<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class Splitter extends TaskController
{
	public $name = 'splitter';
	public $label = 'Splitter';
	public $description = 'Split column into multiple columns';
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
		$key   = $config['key'];
		$field = $data[ $key ];

		$split = explode( $config['separator'], $field );

		for ( $i = 0; $i < count( $split ); $i++ ) {
			$data[ $key . $config['postfix'] . $i ] = $split[ $i ];
		}

		return $data;
	}
}
