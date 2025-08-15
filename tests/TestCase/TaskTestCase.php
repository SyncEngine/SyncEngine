<?php

namespace SyncEngine\Tests\TestCase;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\Provider\Tasks;

abstract class TaskTestCase extends ExecuteTestCase
{
	protected string $_task;

	public function getTask(): ?TaskModel
	{
		$tasks = static::getContainer()->get( Tasks::class );
		return $tasks->get( $this->_task );
	}

	public function execute( $config, $context = null, $data = [], $get = true ): ExecuteData|array
	{
		if ( ! isset( $config['_class'] ) ) {
			$config['_class'] = $this->_task;
		}
		if ( ! isset( $config['_ref'] ) ) {
			$config['_ref'] = str_replace( '.', '', microtime( true ) );
		}
		if ( ! $context ) {
			$context = $this->getContext();
		}
		if ( ! $data instanceof ExecuteData ) {
			$data = new ExecuteData( $data );
		}
		$data = $this->getExecute()->executeTask( $config, $context, $data );
		return ( $get ) ? $data->get() : $data;
	}

	public function testExists() {
		$this->assertInstanceOf( TaskModel::class, $this->getTask() );
	}
}
