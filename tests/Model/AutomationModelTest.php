<?php

namespace SyncEngine\Tests\Model;


use SyncEngine\Model\AutomationModel;
use SyncEngine\Tests\TestCase\BaseTestCase;

class AutomationModelTest extends BaseTestCase
{
	public function testGetAutomationByEndpoint(): void
	{
		$automation = AutomationModel::create();
		$automation->setName('Test Automation');
		$automation->setEndpoint('test-automation');
		$automation->save( true );

		$this->assertEquals( $automation->getRef(), AutomationModel::get( [ 'endpoint' => 'test-automation' ] )?->getRef() );
	}

	public function testIterator(): void
	{
		/** @var AutomationModel $model */
		$model = AutomationModel::create();

		// Without limit.

		$compare = [
			'current' => 0,
			'index'   => -1, // @todo implement index.
			'limit'   => 0,
			'offset'  => 0,
			'size'    => null,
			'isFirst' => false,
			'isLast'  => null,
		];

		$this->assertEquals( $compare, (array) $model->getIteration() );

		$model->enableIterator();
		$model->nextIteration();

		$compare = [
			'current' => 1,
			'index'   => 0, // @todo implement index.
			'limit'   => 0,
			'offset'  => 0,
			'size'    => null,
			'isFirst' => true,
			'isLast'  => null,
		];

		$this->assertEquals( $compare, (array) $model->getIteration() );

		// With limit.

		$model->endIterator();
		$model->setLimit( 50 );

		$compare = [
			'current' => 0,
			'index'   => -1, // @todo implement index.
			'limit'   => 50,
			'offset'  => 0,
			'size'    => null,
			'isFirst' => false,
			'isLast'  => null,
		];

		$this->assertEquals( $compare, (array) $model->getIteration() );

		$model->nextIteration();

		$compare = [
			'current' => 1,
			'index'   => 0, // @todo implement index.
			'limit'   => 50,
			'offset'  => 0,
			'size'    => null,
			'isFirst' => true,
			'isLast'  => null,
		];

		$this->assertEquals( $compare, (array) $model->getIteration() );

		$model->nextIteration();

		$compare = [
			'current' => 2,
			'index'   => 1, // @todo implement index.
			'limit'   => 50,
			'offset'  => 50,
			'size'    => null,
			'isFirst' => false,
			'isLast'  => null,
		];

		$this->assertEquals( $compare, (array) $model->getIteration() );
	}
}
