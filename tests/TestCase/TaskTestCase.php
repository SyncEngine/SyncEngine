<?php

namespace App\Tests\TestCase;

use App\Model\TaskModel;
use App\Service\Tasks;

abstract class TaskTestCase extends ExecuteTestCase
{
	protected string $_task;

	public function getTask(): ?TaskModel
	{
		return Tasks::getTask( $this->_task );
	}
}
