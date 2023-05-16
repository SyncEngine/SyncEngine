<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class Retriever extends TaskController
{
	public $type = 'retriever';
	public $name = 'Retriever';
	public $description = 'Retrieve your data from your specific connection';

    function getFields(): array
    {
        // TODO: Implement getFields() method.
    }

    function execute(array $config, array $data): array
    {
        // TODO: Implement execute() method.
    }
}
