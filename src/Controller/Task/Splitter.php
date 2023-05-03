<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class Splitter extends TaskController
{
	public function getFields(): array {
		return [
			'key' => [
				'type' => 'text',
			],
			'separator' => [
				'type' => 'text',
			],
			'result' => [
				'type' => 'select',
				'options' => [
					'columns',
					'implode',
				]
			],
			'columns' => [
				'type' => 'mapper',
			],
			'implode' => [ // separator
				'type' => 'text',
			]
		];
	}

	public function execute( array $config, array $data ): array
	{
		return $data;
	}
}
