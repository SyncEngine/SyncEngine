<?php

namespace modules\Capitalization;

use App\Controller\ModuleController;
use App\Model\Trait\Task;

class Capitalization extends ModuleController
{
	use Task;

	public function __construct()
	{
		$this->type = 'capitalization';
		$this->name = 'Capitalization';
		$this->description = 'Capitalize certain words';

		parent::__construct();
	}

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
