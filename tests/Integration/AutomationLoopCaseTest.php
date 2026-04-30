<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Task\Loop;
use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationLoopCaseTest extends AutomationScenarioTestCase
{
	public function testAutomationLoopProcessesEachRowIndependently(): void
	{
		$routine = $this->createRoutineScenario( 'Loop Routine', [
			[
				'_class' => Loop::_getClassLocator(),
				'_ref' => 'line_loop',
				'key' => 'items',
				'action' => 'tasks',
				'tasks' => [
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'mark_item',
						'params' => [
							[
								'key' => 'processed',
								'value' => 'yes',
							],
							[
								'key' => 'label',
								'value' => '{{ data.sku }}-done',
							],
						],
					],
				],
			],
		] );

		$automation = $this->createAutomationScenario( 'Loop Automation', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $routine->getId(),
		] );

		$result = $this->executeAutomationScenario( $automation, [
			'items' => [
				[ 'sku' => 'SKU-1' ],
				[ 'sku' => 'SKU-2' ],
			],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'yes', $result['data']['items'][0]['processed'] );
		$this->assertSame( 'SKU-1-done', $result['data']['items'][0]['label'] );
		$this->assertSame( 'yes', $result['data']['items'][1]['processed'] );
		$this->assertSame( 'SKU-2-done', $result['data']['items'][1]['label'] );
	}
}
