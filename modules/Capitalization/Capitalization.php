<?php

namespace modules\Capitalization;

use App\Model\ModuleModel;

class Capitalization extends ModuleModel
{
	public function __construct()
	{
		$this->name = 'Capitalization';
		$this->description = 'Capitalize certain words';

		parent::__construct();
	}
}
