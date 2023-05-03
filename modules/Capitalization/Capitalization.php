<?php

namespace modules\Capitalization;

use App\Controller\ModuleController;

class Capitalization extends ModuleController
{
	public function executeTask( string $task, array $config, array $data ): array
	{
		$fixTag = $config['tag'];
		$data[ $fixTag ] = ucwords( $data[ $fixTag ] );

		return $data;
	}

}
