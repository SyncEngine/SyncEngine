<?php

namespace SyncEngine\Tests\Service;

use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\CodecModel;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecuteLocalBatch;
use SyncEngine\Tests\TestCase\BaseTestCase;

class TaggedLocatorTest extends BaseTestCase
{
	public function testSharedServices()
	{
		$codec1 = CodecModel::get('Json');
		$codec2 = CodecModel::get('Json');

		$this->assertFalse( $codec1 === $codec2 );

		$column1 = ColumnModel::get('Text');
		$column2 = ColumnModel::get('Text');

		$this->assertFalse( $column1 === $column2 );

		$task1 = TaskModel::get('Set');
		$task2 = TaskModel::get('Set');

		$this->assertFalse( $task1 === $task2 );

		$webservice1 = WebserviceModel::get('Http');
		$webservice2 = WebserviceModel::get('Http');

		$this->assertFalse( $webservice1 === $webservice2 );
	}
}
