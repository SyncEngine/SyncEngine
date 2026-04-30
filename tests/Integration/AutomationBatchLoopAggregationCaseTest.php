<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Task\Cache;
use SyncEngine\Task\Loop;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationBatchLoopAggregationCaseTest extends AutomationScenarioTestCase
{
	public function testLoopBatchMethodAggregatesPerBatchMetadataInCache(): void
	{
		$routine = $this->createRoutineScenario( 'Loop Batch Aggregation Routine', [
			[
				'_class' => Loop::_getClassLocator(),
				'_ref' => 'batch_loop',
				'key' => 'rows',
				'method' => 'batch',
				'batch' => 2,
				'action' => 'tasks',
				'tasks' => [
					[
						'_class' => Cache::_getClassLocator(),
						'_ref' => 'append_batch_summary',
						'action' => 'set',
						'source' => 'manual',
						'manual' => '{{ context.loop.iteration.current }}:{{ data|count }}',
						'tag' => 'loop.batches',
						'method' => 'append',
					],
				],
			],
		] );

		$automation = $this->createAutomationScenario( 'Loop Batch Aggregation Automation', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $routine->getId(),
		] );

		$result = $this->executeAutomationScenario( $automation, [
			'rows' => [
				[ 'sku' => 'R-1' ],
				[ 'sku' => 'R-2' ],
				[ 'sku' => 'R-3' ],
				[ 'sku' => 'R-4' ],
				[ 'sku' => 'R-5' ],
			],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertCount( 5, $result['data']['rows'] );
		$this->assertSame( [ '1:2', '2:2', '3:1' ], $this->getLastAutomationContext()?->getCacheTag( 'loop.batches' ) );
	}
}
