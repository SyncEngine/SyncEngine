<?php

namespace modules\Capitalization;

use App\Controller\ModuleController;
use App\Controller\TaskController;

class Capitalization extends TaskController
{
	public $type = 'capitalization';
	public $name = 'Capitalization';
	public $description = 'Capitalize certain words';

	function getFields(): array
	{
		return [];
	}

	function execute(array $config, array $data): array
	{
		$fixTag = $config['tag'];
		$data[ $fixTag ] = ucwords( $data[ $fixTag ] );

		return $data;
	}

}
