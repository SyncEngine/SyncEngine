<?php

namespace App\Tests\TestCase;

use App\Model\TaskModel;
use App\Service\Tasks;

abstract class TaskTestCase extends ExecuteTestCase
{
	protected string $_task;

	public function getTask(): ?TaskModel
	{
		$tasks = static::getContainer()->get( Tasks::class );
		return $tasks->getTask( $this->_task );
	}
}
