<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class Sender extends TaskController
{
	public $type = 'sender';
	public $name = 'Sender';
	public $description = 'Send your data to your specific connection';

    function getFields(): array
    {
		return [
			'key' => [
				'Authentication type' => ['dingen', 'spullen', 'so door'],
			],
		];
    }

    function execute(array $config, array $data): array
    {
        // TODO: Implement execute() method.
    }
}
