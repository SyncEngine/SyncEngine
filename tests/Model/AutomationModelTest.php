<?php

namespace SyncEngine\Tests\Model;


use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;
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
		$trace = TraceModel::create();

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
		$trace->nextIteration();

		$compare = [
			'current' => 1,
			'index'   => 0, // @todo implement index.
			'limit'   => 0,
			'offset'  => 0,
			'size'    => null,
			'isFirst' => true,
			'isLast'  => null,
		];

		$this->assertEquals( $compare, (array) $model->getIteration( $trace ) );

		// With limit.

		$trace->resetIterator();
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

		$this->assertEquals( $compare, (array) $model->getIteration( $trace ) );

		$trace->nextIteration();

		$compare = [
			'current' => 1,
			'index'   => 0, // @todo implement index.
			'limit'   => 50,
			'offset'  => 0,
			'size'    => null,
			'isFirst' => true,
			'isLast'  => null,
		];

		$this->assertEquals( $compare, (array) $model->getIteration( $trace ) );

		$trace->nextIteration();

		$compare = [
			'current' => 2,
			'index'   => 1, // @todo implement index.
			'limit'   => 50,
			'offset'  => 50,
			'size'    => null,
			'isFirst' => false,
			'isLast'  => null,
		];

		$this->assertEquals( $compare, (array) $model->getIteration( $trace ) );
	}

	public function testIteratorEnabledWithoutTraceReturnsDefaults(): void
	{
		/** @var AutomationModel $model */
		$model = AutomationModel::create();
		$model->enableIterator();
		$model->setLimit( 25 );

		$compare = [
			'current' => 0,
			'index'   => -1,
			'limit'   => 25,
			'offset'  => 0,
			'size'    => null,
			'isFirst' => false,
			'isLast'  => null,
		];

		$this->assertEquals( $compare, (array) $model->getIteration() );
	}

	public function testTagsResourceUsesTraceIterationWhenContextHasTrace(): void
	{
		/** @var AutomationModel $model */
		$model = AutomationModel::create();
		$model->enableIterator();
		$model->setLimit( 50 );

		$trace = TraceModel::create();
		$trace->setCurrentIteration( 3 );

		/** @var Execute $execute */
		$execute = static::getContainer()->get( Execute::class );
		$context = new ExecuteContext( $execute, $model );
		$context->registerTrace( $trace );

		$iteration = $model->getTagsResource( [], $context )['iteration'];

		$this->assertSame( 3, $iteration['current'] );
		$this->assertSame( 2, $iteration['index'] );
		$this->assertSame( 100, $iteration['offset'] );
		$this->assertSame( 50, $iteration['limit'] );
	}

	public function testContextTagsExposeIteratorAndIterationAlias(): void
	{
		/** @var AutomationModel $model */
		$model = AutomationModel::create();
		$model->enableIterator();
		$model->setLimit( 20 );

		$trace = TraceModel::create();
		$trace->setCurrentIteration( 2 );

		/** @var Execute $execute */
		$execute = static::getContainer()->get( Execute::class );
		$context = new ExecuteContext( $execute, $model );
		$context->registerTrace( $trace );

		$tags = $context->getTagsResource();

		$this->assertArrayHasKey( 'iteration', $tags );
		$this->assertSame( 2, $tags['iteration']['current'] );
		$this->assertSame( 20, $tags['iteration']['limit'] );
	}

	public function testIsScheduledUsesPersistedTraceStatus(): void
	{
		/** @var AutomationModel $model */
		$model = AutomationModel::create();

		$this->assertFalse( $model->isScheduled() );

		$model->setName( 'Scheduled Check' );
		$model->setEndpoint( 'scheduled-check-' . uniqid() );
		$model->save( true );

		$trace = TraceModel::create();
		$trace->setStatus( TraceStatus::SCHEDULED );
		$trace->register( $model );
		$trace->save( true );

		$this->assertTrue( $model->isScheduled() );

		$trace->setStatus( TraceStatus::SUCCESS );
		$trace->save( true );

		$this->assertFalse( $model->isScheduled() );
	}

	public function testActiveRunsUsePersistedRunningTraceStatus(): void
	{
		/** @var AutomationModel $model */
		$model = AutomationModel::create();
		$model->setName( 'Running Check' );
		$model->setEndpoint( 'running-check-' . uniqid() );
		$model->save( true );

		$trace = TraceModel::create();
		$trace->setStatus( TraceStatus::RUNNING );
		$trace->register( $model );
		$trace->save( true );

		$this->assertTrue( $model->hasActiveRuns() );

		$activeRuns = $model->getActiveRuns();

		$this->assertCount( 1, $activeRuns );
		$this->assertInstanceOf( TraceModel::class, $activeRuns[0] );
		$this->assertSame( $trace->getId(), $activeRuns[0]->getId() );
		$this->assertSame( TraceStatus::RUNNING, $activeRuns[0]->getStatus() );
	}
}
