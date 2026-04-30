<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Task\Cache;
use SyncEngine\Task\Set;
use SyncEngine\Task\Trigger;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationTriggerCacheCaseTest extends AutomationScenarioTestCase
{
	public function testTriggerCanReuseCacheAndInjectedVariables(): void
	{
		$nestedRoutine = $this->createRoutineScenario( 'Triggered Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'copy_from_cache',
				'params' => [
					[
						'key' => 'copied_customer',
						'value' => '{{ cache.current.customer }}',
					],
					[
						'key' => 'note',
						'value' => '{{ variables.note }}',
					],
				],
			],
		] );

		$automationRoutine = $this->createRoutineScenario( 'Trigger Cache Routine', [
			[
				'_class' => Cache::_getClassLocator(),
				'_ref' => 'cache_customer',
				'action' => 'set',
				'source' => 'manual',
				'manual' => '{{ data.customer_id }}',
				'tag' => 'current.customer',
			],
			[
				'_class' => Trigger::_getClassLocator(),
				'_ref' => 'trigger_nested_routine',
				'action' => 'routine',
				'routine' => $nestedRoutine->getId(),
				'pass_data' => true,
				'override_data' => true,
				'variables' => [
					'note' => 'synced-from-trigger',
				],
			],
		] );

		$automation = $this->createAutomationScenario( 'Trigger Cache Automation', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $automationRoutine->getId(),
		] );

		$result = $this->executeAutomationScenario( $automation, [
			'customer_id' => 'C-9001',
			'payload' => 'keep-me',
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'C-9001', $result['data']['customer_id'] );
		$this->assertSame( 'keep-me', $result['data']['payload'] );
		$this->assertSame( 'C-9001', $result['data']['copied_customer'] );
		$this->assertSame( 'synced-from-trigger', $result['data']['note'] );
	}
}
