<?php

namespace App\Controller\Task;

use App\Controller\ModuleController;

class Iterator extends ModuleController
{
	public function executeTask( array $config, array $data ): array
	{
		return $data;
	}
}
