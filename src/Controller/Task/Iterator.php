<?php

namespace App\Controller\Task;

use App\Controller\ModuleController;

class Iterator extends ModuleController
{
	public function executeStep( array $config, array $data ): array
	{
		return $data;
	}
}
