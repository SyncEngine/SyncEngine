<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationRoutineConditionsCaseTest extends AutomationScenarioTestCase
{
	public function testRoutineConditionsControlWhetherTasksRun(): void
	{
		$routine = $this->createRoutineScenario(
			'Conditional Routine',
			[
				[
					'_class' => Set::_getClassLocator(),
					'_ref' => 'set_priority',
					'params' => [
						[
							'key' => 'priority',
							'value' => 'expedite',
						],
					],
				],
			],
			[
				[
					'key' => 'type',
					'operator' => '==',
					'compare' => 'vip',
				],
			]
		);

		$automation = $this->createAutomationScenario( 'Conditional Routine Automation', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $routine->getId(),
		] );

		$vipResult = $this->executeAutomationScenario( $automation, [ 'type' => 'vip', 'order' => 'A100' ] );
		$this->assertTrue( $vipResult['success'] );
		$this->assertSame( 'expedite', $vipResult['data']['priority'] );

		$regularResult = $this->executeAutomationScenario( $automation, [ 'type' => 'regular', 'order' => 'A101' ] );
		$this->assertTrue( $regularResult['success'] );
		$this->assertArrayNotHasKey( 'priority', $regularResult['data'] );
		$this->assertSame( 'regular', $regularResult['data']['type'] );
	}
}
