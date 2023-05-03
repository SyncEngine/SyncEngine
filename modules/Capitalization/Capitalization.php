<?php

namespace modules\Capitalization;

use App\Controller\ModuleController;
use App\Controller\TaskController;

class Capitalization extends ModuleController
{
	public function getTask( string $name ): TaskController {
		// TODO: Implement getTask() method.
	}

	public function executeTask( string $task, array $config, array $data ): array
	{
		$fixTag = $config['tag'];
		$data[ $fixTag ] = ucwords( $data[ $fixTag ] );

		return $data;
	}

}
