<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Service\ExecuteLocalBatch;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationIteratorLocalBatchCaseTest extends AutomationScenarioTestCase
{
	public function testIteratorLocalBatchCreatesStoredBatchesAndSchedulesContinuation(): void
	{
		$automation = $this->createAutomationScenario( 'Iterator Local Batch Automation', [
			'source' => [ 'request' ],
			'iterator' => true,
			'batch_method' => 'local',
			'limit' => 2,
			'actions' => [],
		] );

		$result = $this->executeAutomationScenario( $automation, [
			[ 'sku' => 'A-1' ],
			[ 'sku' => 'A-2' ],
			[ 'sku' => 'A-3' ],
			[ 'sku' => 'A-4' ],
			[ 'sku' => 'A-5' ],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'Added to queue!', $result['message'] );
		$this->assertCount( 2, $result['data'] );

		$context = $this->getLastAutomationContext();
		$trace = $context?->getTrace();

		$this->assertNotNull( $trace );

		$batches = ExecuteLocalBatch::load( $trace );
		$this->assertSame( [ 'A-1', 'A-2' ], array_column( $batches->fetchBatch( 1 ), 'sku' ) );
		$this->assertSame( [ 'A-3', 'A-4' ], array_column( $batches->fetchBatch( 2 ), 'sku' ) );
		$this->assertSame( [ 'A-5' ], array_column( $batches->fetchBatch( 3 ), 'sku' ) );
	}
}
