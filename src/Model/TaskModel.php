<?php

namespace App\Model;

use App\Model\Trait\Task;

abstract class TaskModel
{
	use Task;

	public $type = '';
	public $name = '';
	public $description = '';

	final static function isTask( $class ): bool
	{
		return in_array( "App\Model\Trait\Task", class_uses( $class ), true );
	}
}
