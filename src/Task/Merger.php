<?php

namespace App\Task;

use App\Model\TaskModel;

class Merger extends TaskModel
{
	public function __construct()
	{
		$this->type = 'merger';
		$this->name = 'Merger';
		$this->description = 'Merge columns into single column';

		parent::__construct();
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
