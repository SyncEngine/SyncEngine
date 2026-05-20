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
}


