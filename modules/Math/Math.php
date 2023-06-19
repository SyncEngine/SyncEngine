<?php

namespace modules\Math;

use App\Model\ModuleModel;

class Math extends ModuleModel
{
	public function __construct()
	{
		$this->type = 'math';
		$this->name = 'Math';
		$this->description = 'Simple field math';

		parent::__construct();
	}
}
