<?php

namespace SyncEngine\Tests\TestCase;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Tasks;

abstract class TaskTestCase extends ExecuteTestCase
{
	protected string $_task;

	public function getTask(): ?TaskModel
	{
		$tasks = static::getContainer()->get( Tasks::class );
		return $tasks->getTask( $this->_task );
	}

	public function execute( $config, $context = null, $data = [] ): array
	{
		if ( ! isset( $config['_class'] ) ) {
			$config['_class'] = $this->_task;
		}
		if ( ! $context ) {
			$context = $this->getContext();
		}
		return $this->getExecute()->executeTask( $config, $context, $data );
	}
}
