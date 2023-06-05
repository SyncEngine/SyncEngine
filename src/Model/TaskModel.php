<?php

namespace App\Model;

use App\Model\Trait\Task;
use App\Service\ModuleService;

abstract class TaskModel
{
	use Task;

	public $type = '';
	public $name = '';
	public $description = '';
	public $module = null;

	public function __construct()
	{
		// Construct.
	}

	final static function isTask( $class ): bool
	{
		return $class instanceof TaskModel || in_array( "App\Model\Trait\Task", class_uses( $class ), true );
	}
}
