<?php

namespace SyncEngine\Tests\Integration;

use Symfony\Component\HttpFoundation\Request;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Messenger\Handler\AutomationBatchHandler;
use SyncEngine\Messenger\Message\AutomationBatch;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\AutomationMode;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationQueuedModeCaseTest extends AutomationScenarioTestCase
{
	public function testQueuedRequestsPersistPayloadAndAreConsumedOneByOne(): void
	{
		$automation = $this->createAutomationScenario( 'Queued Mode Automation', [
			'execution' => [
				'mode' => AutomationMode::QUEUED->value,
			],
			'actions' => [],
		] );

		$activeTrace = TraceModel::create();
		$activeTrace->setStatus( TraceStatus::RUNNING )->register( $automation )->save( true );

		/** @var Execute $execute */
		$execute = static::getContainer()->get( Execute::class );

		$contextOne = new ExecuteContext( $execute, $automation );
		$contextOne->registerRequest( new Request( [ 'q' => 'one' ], [ 'id' => 1 ] ) );
		$execute->schedule( $automation, $contextOne );

		$contextTwo = new ExecuteContext( $execute, $automation );
		$contextTwo->registerRequest( new Request( [ 'q' => 'two' ], [ 'id' => 2 ] ) );
		$execute->schedule( $automation, $contextTwo );

		$queued = TraceModel::getRepository()->findBy(
			[ 'automation' => $automation->getId(), 'status' => TraceStatus::QUEUED->value ],
			[ 'created' => 'ASC' ]
		);

		$this->assertCount( 2, $queued );
		$this->assertSame( [ 'id' => 1 ], (array) ( $queued[0]->getTrace()['queue']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'one' ], (array) ( $queued[0]->getTrace()['queue']['query'] ?? [] ) );
		$this->assertSame( [ 'id' => 2 ], (array) ( $queued[1]->getTrace()['queue']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'two' ], (array) ( $queued[1]->getTrace()['queue']['query'] ?? [] ) );

		$activeTrace->setStatus( TraceStatus::SUCCESS )->save( true );
		DefaultController::getEntityManager()->clear();

		$this->assertCount(
			0,
			TraceModel::getRepository()->findBy( [
				'automation' => $automation->getId(),
				'status' => TraceStatus::RUNNING->value,
			] )
		);
		$this->assertTrue( AutomationModel::get( $automation->getId() )->canRunNow() );

		/** @var AutomationBatchHandler $handler */
		$handler = static::getContainer()->get( AutomationBatchHandler::class );
		$handler->__invoke( new AutomationBatch( $automation->getId(), (int) $queued[0]->getId() ) );

		$firstQueued = TraceModel::load( $automation, (int) $queued[0]->getId() );
		$secondQueued = TraceModel::load( $automation, (int) $queued[1]->getId() );

		$this->assertNotSame( TraceStatus::QUEUED, $firstQueued->getStatus() );
		$this->assertArrayNotHasKey( 'queue', (array) $firstQueued->getEntity()->getTrace() );
		$this->assertSame( TraceStatus::QUEUED, $secondQueued->getStatus() );
		$this->assertArrayHasKey( 'queue', (array) $secondQueued->getEntity()->getTrace() );
	}

	public function testQueuedRequestsRemainProcessableAfterSwitchingAutomationToSingle(): void
	{
		$automation = $this->createAutomationScenario( 'Queued To Single Automation', [
			'execution' => [
				'mode' => AutomationMode::QUEUED->value,
			],
			'actions' => [],
		] );

		$activeTrace = TraceModel::create();
		$activeTrace->setStatus( TraceStatus::RUNNING )->register( $automation )->save( true );

		/** @var Execute $execute */
		$execute = static::getContainer()->get( Execute::class );

		$contextOne = new ExecuteContext( $execute, $automation );
		$contextOne->registerRequest( new Request( [ 'q' => 'one' ], [ 'id' => 1 ] ) );
		$execute->schedule( $automation, $contextOne );

		$contextTwo = new ExecuteContext( $execute, $automation );
		$contextTwo->registerRequest( new Request( [ 'q' => 'two' ], [ 'id' => 2 ] ) );
		$execute->schedule( $automation, $contextTwo );

		$queued = TraceModel::getRepository()->findBy(
			[ 'automation' => $automation->getId(), 'status' => TraceStatus::QUEUED->value ],
			[ 'created' => 'ASC' ]
		);

		$this->assertCount( 2, $queued );

		$automation->setConfig( [
			'execution' => [
				'mode' => AutomationMode::SINGLE->value,
			],
			'actions' => [],
		] );
		$automation->save( true );

		$activeTrace->setStatus( TraceStatus::SUCCESS )->save( true );
		DefaultController::getEntityManager()->clear();

		/** @var AutomationBatchHandler $handler */
		$handler = static::getContainer()->get( AutomationBatchHandler::class );
		$handler->__invoke( new AutomationBatch( $automation->getId(), (int) $queued[0]->getId() ) );

		$firstQueued = TraceModel::load( $automation, (int) $queued[0]->getId() );
		$secondQueued = TraceModel::load( $automation, (int) $queued[1]->getId() );

		$this->assertNotSame( TraceStatus::QUEUED, $firstQueued->getStatus() );
		$this->assertArrayNotHasKey( 'queue', (array) $firstQueued->getEntity()->getTrace() );
		$this->assertSame( TraceStatus::QUEUED, $secondQueued->getStatus() );
		$this->assertArrayHasKey( 'queue', (array) $secondQueued->getEntity()->getTrace() );

		DefaultController::getEntityManager()->clear();
		$handler->__invoke( new AutomationBatch( $automation->getId(), (int) $queued[1]->getId() ) );

		$finalQueued = TraceModel::load( $automation, (int) $queued[1]->getId() );
		$this->assertNotSame( TraceStatus::QUEUED, $finalQueued->getStatus() );
		$this->assertArrayNotHasKey( 'queue', (array) $finalQueued->getEntity()->getTrace() );
	}

	public function testQueuedRequestsRemainProcessableAfterSwitchingAutomationToParallel(): void
	{
		$automation = $this->createAutomationScenario( 'Queued To Parallel Automation', [
			'execution' => [
				'mode' => AutomationMode::QUEUED->value,
			],
			'actions' => [],
		] );

		$activeTrace = TraceModel::create();
		$activeTrace->setStatus( TraceStatus::RUNNING )->register( $automation )->save( true );

		/** @var Execute $execute */
		$execute = static::getContainer()->get( Execute::class );

		$contextOne = new ExecuteContext( $execute, $automation );
		$contextOne->registerRequest( new Request( [ 'q' => 'parallel-1' ], [ 'id' => 1 ] ) );
		$execute->schedule( $automation, $contextOne );

		$contextTwo = new ExecuteContext( $execute, $automation );
		$contextTwo->registerRequest( new Request( [ 'q' => 'parallel-2' ], [ 'id' => 2 ] ) );
		$execute->schedule( $automation, $contextTwo );

		$contextThree = new ExecuteContext( $execute, $automation );
		$contextThree->registerRequest( new Request( [ 'q' => 'parallel-3' ], [ 'id' => 3 ] ) );
		$execute->schedule( $automation, $contextThree );

		$queued = TraceModel::getRepository()->findBy(
			[ 'automation' => $automation->getId(), 'status' => TraceStatus::QUEUED->value ],
			[ 'created' => 'ASC' ]
		);

		$this->assertCount( 3, $queued );
		$this->assertSame( [ 'id' => 1 ], (array) ( $queued[0]->getTrace()['queue']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'parallel-1' ], (array) ( $queued[0]->getTrace()['queue']['query'] ?? [] ) );
		$this->assertSame( [ 'id' => 2 ], (array) ( $queued[1]->getTrace()['queue']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'parallel-2' ], (array) ( $queued[1]->getTrace()['queue']['query'] ?? [] ) );
		$this->assertSame( [ 'id' => 3 ], (array) ( $queued[2]->getTrace()['queue']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'parallel-3' ], (array) ( $queued[2]->getTrace()['queue']['query'] ?? [] ) );

		$automation->setConfig( [
			'execution' => [
				'mode' => AutomationMode::PARALLEL->value,
			],
			'actions' => [],
		] );
		$automation->save( true );

		$activeTrace->setStatus( TraceStatus::SUCCESS )->save( true );
		DefaultController::getEntityManager()->clear();

		/** @var AutomationBatchHandler $handler Emulate Messenger triggering a new run */
		$handler = static::getContainer()->get( AutomationBatchHandler::class );
		$handler->__invoke( new AutomationBatch( $automation->getId(), (int) $queued[0]->getId() ) );

		$firstProcessed = TraceModel::load( $automation, (int) $queued[0]->getId() );
		$secondStillQueued = TraceModel::load( $automation, (int) $queued[1]->getId() );
		$thirdStillQueued = TraceModel::load( $automation, (int) $queued[2]->getId() );

		$this->assertSame( TraceStatus::SUCCESS, $firstProcessed->getStatus() );
		$this->assertArrayNotHasKey( 'queue', (array) $firstProcessed->getEntity()->getTrace() );
		$this->assertSame( TraceStatus::QUEUED, $secondStillQueued->getStatus() );
		$this->assertSame( TraceStatus::QUEUED, $thirdStillQueued->getStatus() );

		DefaultController::getEntityManager()->clear();
		$handler->__invoke( new AutomationBatch( $automation->getId(), (int) $queued[1]->getId() ) );

		$secondProcessed = TraceModel::load( $automation, (int) $queued[1]->getId() );
		$thirdStillQueued = TraceModel::load( $automation, (int) $queued[2]->getId() );

		$this->assertSame( TraceStatus::SUCCESS, $secondProcessed->getStatus() );
		$this->assertArrayNotHasKey( 'queue', (array) $secondProcessed->getEntity()->getTrace() );
		$this->assertSame( TraceStatus::QUEUED, $thirdStillQueued->getStatus() );

		DefaultController::getEntityManager()->clear();
		$handler->__invoke( new AutomationBatch( $automation->getId(), (int) $queued[2]->getId() ) );

		$thirdProcessed = TraceModel::load( $automation, (int) $queued[2]->getId() );
		$this->assertSame( TraceStatus::SUCCESS, $thirdProcessed->getStatus() );
		$this->assertArrayNotHasKey( 'queue', (array) $thirdProcessed->getEntity()->getTrace() );
	}

	public function testQueuedModeQueuesNewScheduleWhenScheduledTraceExists(): void
	{
		$automation = $this->createAutomationScenario( 'Queued While Scheduled Automation', [
			'execution' => [
				'mode' => AutomationMode::QUEUED->value,
			],
			'actions' => [],
		] );

		TraceModel::create()->setStatus( TraceStatus::SCHEDULED )->register( $automation )->save( true );

		/** @var Execute $execute */
		$execute = static::getContainer()->get( Execute::class );

		$context = new ExecuteContext( $execute, $automation );
		$context->registerRequest( new Request( [ 'q' => 'scheduled' ], [ 'id' => 10 ] ) );
		$execute->schedule( $automation, $context );

		$scheduled = TraceModel::getRepository()->findBy( [
			'automation' => $automation->getId(),
			'status' => TraceStatus::SCHEDULED->value,
		] );
		$queued = TraceModel::getRepository()->findBy( [
			'automation' => $automation->getId(),
			'status' => TraceStatus::QUEUED->value,
		] );

		$this->assertCount( 1, $scheduled );
		$this->assertCount( 1, $queued );
		$this->assertSame( [ 'id' => 10 ], (array) ( $queued[0]->getTrace()['queue']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'scheduled' ], (array) ( $queued[0]->getTrace()['queue']['query'] ?? [] ) );
	}
}


