<?php

namespace modules\Prestashop;

use App\Model\ModuleModel;

class Prestashop extends ModuleModel
{
	public function __construct()
	{
		$this->name = 'Prestashop';
		$this->description = 'Prestashop 1.7 module';

		parent::__construct();
	}
}
