<?php

namespace SyncEngine\Tests\Runtime;

use Symfony\Component\HttpFoundation\Request;
use SyncEngine\Controller\Api\ApiEndpointController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\AutomationMode;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;
use SyncEngine\Runtime\Execute;
use SyncEngine\Runtime\ExecuteScheduler;
use SyncEngine\Tests\TestCase\RuntimeScenarioTestCase;

class ApiEndpointStatusCaseTest extends RuntimeScenarioTestCase
{
	public function testStatusReturnsIdlePayload(): void
	{
		$automation = $this->createAutomationScenario( 'Status Idle Automation', [
			'execution' => [
				'mode' => AutomationMode::SINGLE->value,
			],
			'actions' => [],
		] );

		$payload = $this->requestStatus( $automation );

		$this->assertTrue( $payload['success'] );
		$this->assertSame( 'idle', $payload['status'] );
		$this->assertTrue( $payload['can_execute'] );
		$this->assertSame( $this->isSchedulingAvailableFor( $automation ), $payload['can_schedule'] );
		$this->assertSame( [], $payload['running'] );
		$this->assertSame( [], $payload['scheduled'] );
		$this->assertSame( [], $payload['queued'] );
	}

	public function testStatusReturnsRunningScheduledAndQueuedTraceInfo(): void
	{
		$automation = $this->createAutomationScenario( 'Status Active Automation', [
			'execution' => [
				'mode' => AutomationMode::QUEUED->value,
			],
			'actions' => [],
		] );

		$running = TraceModel::create();
		$running->setStatus( TraceStatus::RUNNING )->register( $automation );
		$running->setCurrentIteration( 3 );
		$running->store();

		TraceModel::create()->setStatus( TraceStatus::SCHEDULED )->register( $automation )->save( true );
		TraceModel::create()->setStatus( TraceStatus::QUEUED )->register( $automation )->save( true );

		$payload = $this->requestStatus( $automation );

		$this->assertTrue( $payload['success'] );
		$this->assertSame( 'active', $payload['status'] );
		$this->assertFalse( $payload['can_execute'] );
		$this->assertSame( $this->isSchedulingAvailableFor( $automation ), $payload['can_schedule'] );

		$this->assertCount( 1, $payload['running'] );
		$this->assertCount( 1, $payload['scheduled'] );
		$this->assertCount( 1, $payload['queued'] );

		$this->assertTraceSummary( $payload['running'][0], TraceStatus::RUNNING->value, true );
		$this->assertSame( 3, $payload['running'][0]['current_iteration'] );

		$this->assertTraceSummary( $payload['scheduled'][0], TraceStatus::SCHEDULED->value );
		$this->assertArrayNotHasKey( 'current_iteration', $payload['scheduled'][0] );

		$this->assertTraceSummary( $payload['queued'][0], TraceStatus::QUEUED->value );
		$this->assertArrayNotHasKey( 'current_iteration', $payload['queued'][0] );
	}

	private function requestStatus( AutomationModel $automation ): array
	{
		/** @var ApiEndpointController $controller */
		$controller = static::getContainer()->get( ApiEndpointController::class );
		/** @var Execute $execute */
		$execute = static::getContainer()->get( Execute::class );

		$response = $controller->endpoint( (string) $automation->getEndpoint(), 'status', $execute, new Request() );

		return json_decode( $response->getContent(), true ) ?? [];
	}

	private function isSchedulingAvailableFor( AutomationModel $automation ): bool
	{
		/** @var ExecuteScheduler $scheduler */
		$scheduler = static::getContainer()->get( ExecuteScheduler::class );

		return $scheduler->canSchedule( $automation );
	}

	private function assertTraceSummary( array $trace, string $status, bool $withIteration = false ): void
	{
		$this->assertArrayHasKey( 'id', $trace );
		$this->assertArrayHasKey( 'created', $trace );
		$this->assertArrayHasKey( 'modified', $trace );
		$this->assertArrayHasKey( 'status', $trace );
		$this->assertSame( $status, $trace['status'] );
		$this->assertIsString( $trace['created'] );
		$this->assertIsString( $trace['modified'] );

		if ( $withIteration ) {
			$this->assertArrayHasKey( 'current_iteration', $trace );
		}
	}
}

