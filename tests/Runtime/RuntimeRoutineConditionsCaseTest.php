<?php

namespace SyncEngine\Tests\Runtime;

use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\RuntimeScenarioTestCase;

class RuntimeRoutineConditionsCaseTest extends RuntimeScenarioTestCase
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
		$this->assertTrue( $vipResult->isSuccess() );
		$this->assertSame( 'expedite', $vipResult->getData()['priority'] );

		$regularResult = $this->executeAutomationScenario( $automation, [ 'type' => 'regular', 'order' => 'A101' ] );
		$this->assertTrue( $regularResult->isSuccess() );
		$this->assertArrayNotHasKey( 'priority', $regularResult->getData() );
		$this->assertSame( 'regular', $regularResult->getData()['type'] );
	}
}
