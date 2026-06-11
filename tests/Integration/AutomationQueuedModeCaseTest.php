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
use SyncEngine\Runtime\Execute;
use SyncEngine\Runtime\ExecuteContext;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationQueuedModeCaseTest extends AutomationScenarioTestCase
{

	/**
	 * In tests we invoke handlers directly (synchronous), not through an async worker.
	 * Once this call returns, the current run is already finished and scheduler side-effects
	 * (for example QUEUED -> SCHEDULED promotion of the next trace) may already be visible.
	 */
	private function invokeBatch( AutomationBatchHandler $handler, AutomationModel $automation, int $traceId ): void
	{
		$handler->__invoke( new AutomationBatch( $automation->getId(), $traceId ) );
	}

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
		$this->assertSame( [ 'id' => 1 ], (array) ( $queued[0]->getTrace()['request']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'one' ], (array) ( $queued[0]->getTrace()['request']['query'] ?? [] ) );
		$this->assertSame( [ 'id' => 2 ], (array) ( $queued[1]->getTrace()['request']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'two' ], (array) ( $queued[1]->getTrace()['request']['query'] ?? [] ) );

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
		$this->invokeBatch( $handler, $automation, (int) $queued[0]->getId() );

		$firstQueued = TraceModel::load( $automation, (int) $queued[0]->getId() );
		$secondQueued = TraceModel::load( $automation, (int) $queued[1]->getId() );

		// After the first run completes, only the immediate next queued trace is promoted to SCHEDULED.
		// See ::invokeBatch() for more info on state changes during tests!
		$this->assertNotSame( TraceStatus::QUEUED, $firstQueued->getStatus() );
		$this->assertNotSame( TraceStatus::SCHEDULED, $firstQueued->getStatus() );
		$this->assertArrayHasKey( 'request', (array) $firstQueued->getEntity()->getTrace() );
		$this->assertSame( TraceStatus::SCHEDULED, $secondQueued->getStatus() );
		$this->assertArrayHasKey( 'request', (array) $secondQueued->getEntity()->getTrace() );
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

		$contextThree = new ExecuteContext( $execute, $automation );
		$contextThree->registerRequest( new Request( [ 'q' => 'three' ], [ 'id' => 3 ] ) );
		$execute->schedule( $automation, $contextThree );

		$queued = TraceModel::getRepository()->findBy(
			[ 'automation' => $automation->getId(), 'status' => TraceStatus::QUEUED->value ],
			[ 'created' => 'ASC' ]
		);

		$this->assertCount( 3, $queued );

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
		$this->invokeBatch( $handler, $automation, (int) $queued[0]->getId() );

		$firstQueued = TraceModel::load( $automation, (int) $queued[0]->getId() );
		$secondQueued = TraceModel::load( $automation, (int) $queued[1]->getId() );
		$thirdQueued = TraceModel::load( $automation, (int) $queued[2]->getId() );

		// Processing #1 promotes only #2 from QUEUED to SCHEDULED; #3 stays QUEUED.
		// See ::invokeBatch() for more info on state changes during tests!
		$this->assertNotSame( TraceStatus::QUEUED, $firstQueued->getStatus() );
		$this->assertNotSame( TraceStatus::SCHEDULED, $firstQueued->getStatus() );
		$this->assertArrayHasKey( 'request', (array) $firstQueued->getEntity()->getTrace() );
		$this->assertSame( TraceStatus::SCHEDULED, $secondQueued->getStatus() );
		$this->assertArrayHasKey( 'request', (array) $secondQueued->getEntity()->getTrace() );
		$this->assertSame( TraceStatus::QUEUED, $thirdQueued->getStatus() );
		$this->assertArrayHasKey( 'request', (array) $thirdQueued->getEntity()->getTrace() );

		DefaultController::getEntityManager()->clear();
		$this->invokeBatch( $handler, $automation, (int) $queued[1]->getId() );

		$secondProcessed = TraceModel::load( $automation, (int) $queued[1]->getId() );
		$thirdStillQueued = TraceModel::load( $automation, (int) $queued[2]->getId() );

		// Processing #2 promotes #3 to SCHEDULED.
		// See ::invokeBatch() for more info on state changes during tests!
		$this->assertNotSame( TraceStatus::QUEUED, $secondProcessed->getStatus() );
		$this->assertNotSame( TraceStatus::SCHEDULED, $secondProcessed->getStatus() );
		$this->assertArrayHasKey( 'request', (array) $secondProcessed->getEntity()->getTrace() );
		$this->assertSame( TraceStatus::SCHEDULED, $thirdStillQueued->getStatus() );

		DefaultController::getEntityManager()->clear();
		$this->invokeBatch( $handler, $automation, (int) $queued[2]->getId() );

		$thirdProcessed = TraceModel::load( $automation, (int) $queued[2]->getId() );
		$this->assertNotSame( TraceStatus::QUEUED, $thirdProcessed->getStatus() );
		$this->assertNotSame( TraceStatus::SCHEDULED, $thirdProcessed->getStatus() );
		$this->assertArrayHasKey( 'request', (array) $thirdProcessed->getEntity()->getTrace() );
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
		$this->assertSame( [ 'id' => 1 ], (array) ( $queued[0]->getTrace()['request']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'parallel-1' ], (array) ( $queued[0]->getTrace()['request']['query'] ?? [] ) );
		$this->assertSame( [ 'id' => 2 ], (array) ( $queued[1]->getTrace()['request']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'parallel-2' ], (array) ( $queued[1]->getTrace()['request']['query'] ?? [] ) );
		$this->assertSame( [ 'id' => 3 ], (array) ( $queued[2]->getTrace()['request']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'parallel-3' ], (array) ( $queued[2]->getTrace()['request']['query'] ?? [] ) );

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
		$this->invokeBatch( $handler, $automation, (int) $queued[0]->getId() );

		$firstProcessed = TraceModel::load( $automation, (int) $queued[0]->getId() );
		$secondStillQueued = TraceModel::load( $automation, (int) $queued[1]->getId() );
		$thirdStillQueued = TraceModel::load( $automation, (int) $queued[2]->getId() );

		// Promotion is one-by-one: after #1 finishes, only #2 becomes SCHEDULED.
		// See ::invokeBatch() for more info on state changes during tests!
		$this->assertSame( TraceStatus::SUCCESS, $firstProcessed->getStatus() );
		$this->assertNotSame( TraceStatus::SCHEDULED, $firstProcessed->getStatus() );
		$this->assertArrayHasKey( 'request', (array) $firstProcessed->getEntity()->getTrace() );
		$this->assertSame( TraceStatus::SCHEDULED, $secondStillQueued->getStatus() );
		$this->assertSame( TraceStatus::QUEUED, $thirdStillQueued->getStatus() );

		DefaultController::getEntityManager()->clear();
		$this->invokeBatch( $handler, $automation, (int) $queued[1]->getId() );

		$secondProcessed = TraceModel::load( $automation, (int) $queued[1]->getId() );
		$thirdStillQueued = TraceModel::load( $automation, (int) $queued[2]->getId() );

		$this->assertSame( TraceStatus::SUCCESS, $secondProcessed->getStatus() );
		$this->assertNotSame( TraceStatus::SCHEDULED, $secondProcessed->getStatus() );
		$this->assertArrayHasKey( 'request', (array) $secondProcessed->getEntity()->getTrace() );
		$this->assertSame( TraceStatus::SCHEDULED, $thirdStillQueued->getStatus() );

		DefaultController::getEntityManager()->clear();
		$this->invokeBatch( $handler, $automation, (int) $queued[2]->getId() );

		$thirdProcessed = TraceModel::load( $automation, (int) $queued[2]->getId() );
		$this->assertSame( TraceStatus::SUCCESS, $thirdProcessed->getStatus() );
		$this->assertNotSame( TraceStatus::SCHEDULED, $thirdProcessed->getStatus() );
		$this->assertArrayHasKey( 'request', (array) $thirdProcessed->getEntity()->getTrace() );
	}

	public function testQueuedTracesRemainProcessableWhileAnotherRunIsActive(): void
	{
		$automation = $this->createAutomationScenario( 'Queued Mode Active Check', [
			'execution' => [
				'mode' => AutomationMode::QUEUED->value,
			],
			'actions' => [],
		] );

		$activeTrace = TraceModel::create();
		$activeTrace->setStatus( TraceStatus::RUNNING )->register( $automation )->save( true );

		/** @var Execute $execute */
		$execute = static::getContainer()->get( Execute::class );

		$context = new ExecuteContext( $execute, $automation );
		$context->registerRequest( new Request( [ 'q' => 'pending' ], [ 'id' => 99 ] ) );
		$execute->schedule( $automation, $context );

		$queued = TraceModel::getRepository()->findBy(
			[ 'automation' => $automation->getId(), 'status' => TraceStatus::QUEUED->value ],
			[ 'created' => 'ASC' ]
		);

		$this->assertCount( 1, $queued );

		// Switch mode to PARALLEL — message dispatch policy is already decided at schedule time.
		$automation->setConfig( [
			'execution' => [
				'mode' => AutomationMode::PARALLEL->value,
			],
			'actions' => [],
		] );
		$automation->save( true );

		// Active trace is still RUNNING — handler must still process already-queued traces.
		/** @var AutomationBatchHandler $handler */
		$handler = static::getContainer()->get( AutomationBatchHandler::class );

		$this->invokeBatch( $handler, $automation, (int) $queued[0]->getId() );

		$processed = TraceModel::load( $automation, (int) $queued[0]->getId() );
		$this->assertNotSame( TraceStatus::QUEUED, $processed->getStatus() );
		$this->assertArrayHasKey( 'request', (array) $processed->getEntity()->getTrace() );
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
		$this->assertSame( [ 'id' => 10 ], (array) ( $queued[0]->getTrace()['request']['params'] ?? [] ) );
		$this->assertSame( [ 'q' => 'scheduled' ], (array) ( $queued[0]->getTrace()['request']['query'] ?? [] ) );
	}
}


