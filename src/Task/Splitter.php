<?php

namespace App\Task;

use App\Model\TaskModel;

class Splitter extends TaskModel
{
	public function __construct()
	{
		$this->type = 'splitter';
		$this->name = 'Splitter';
		$this->description = 'Split column into multiple columns';
	}

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
