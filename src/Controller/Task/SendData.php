<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class SendData extends TaskController
{
	public $type = 'send data';
	public $name = 'Send data';
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
