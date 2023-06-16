<?php

namespace modules\Capitalization;

use App\Component\AutomationContext;
use App\Model\ModuleModel;
use App\Model\Trait\Task;

class Capitalization extends ModuleModel
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

	function execute( array $config, array $data, AutomationContext $context ): array
	{
		$fixTag = $config['tag'];
		$data[ $fixTag ] = ucwords( $data[ $fixTag ] );

		return $data;
	}

}
