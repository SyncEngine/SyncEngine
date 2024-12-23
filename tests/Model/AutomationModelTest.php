<?php

namespace SyncEngine\Tests\Model;


use SyncEngine\Model\AutomationModel;
use SyncEngine\Tests\TestCase\BaseTestCase;

class AutomationModelTest extends BaseTestCase
{
	public function testIterator(): void
	{
		$model = AutomationModel::create();

		// Without limit.

		$compare = [
			'current' => 0,
			'index'   => -1, // @todo implement index.
			'limit'   => 0,
			'offset'  => 0,
		];

		$this->assertEquals( $compare, $model->getIterator() );

		$model->enableIterator();
		$model->nextIteration();

		$compare = [
			'current' => 1,
			'index'   => 0, // @todo implement index.
			'limit'   => 0,
			'offset'  => 0,
		];

		$this->assertEquals( $compare, $model->getIterator() );

		// With limit.

		$model->endIterator();
		$model->setLimit( 50 );

		$compare = [
			'current' => 0,
			'index'   => -1, // @todo implement index.
			'limit'   => 50,
			'offset'  => 0,
		];

		$this->assertEquals( $compare, $model->getIterator() );

		$model->nextIteration();

		$compare = [
			'current' => 1,
			'index'   => 0, // @todo implement index.
			'limit'   => 50,
			'offset'  => 0,
		];

		$this->assertEquals( $compare, $model->getIterator() );

		$model->nextIteration();

		$compare = [
			'current' => 2,
			'index'   => 1, // @todo implement index.
			'limit'   => 50,
			'offset'  => 50,
		];

		$this->assertEquals( $compare, $model->getIterator() );
	}
}
