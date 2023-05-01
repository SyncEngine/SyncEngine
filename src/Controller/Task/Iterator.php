<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class Iterator extends TaskController
{
	public function getFields(): array {
		return [];
	}

	public function execute( array $config, array $data ): array
	{
		return $data;
	}
}
