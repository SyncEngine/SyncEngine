<?php

namespace modules\Capitalization;

use App\Component\AutomationContext;
use App\Model\ModuleModel;
use App\Model\Trait\Task;

class Capitalization extends ModuleModel
{
	public function __construct()
	{
		$this->type = 'capitalization';
		$this->name = 'Capitalization';
		$this->description = 'Capitalize certain words';

		parent::__construct();
	}
}
