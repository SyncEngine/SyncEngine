<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Task\Cache;
use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationEventHookCaseTest extends AutomationScenarioTestCase
{
	public function testAutomationOnSuccessEventStoresMarkerInCache(): void
	{
		$routine = $this->createRoutineScenario( 'Event Hook Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'set_processed',
				'params' => [
					[
						'key' => 'status',
						'value' => 'processed',
					],
				],
			],
		] );

		$automation = $this->createAutomationScenario( 'Success Hook Automation', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $routine->getId(),
			'events' => [
				'on_success' => [
					[
						'_class' => Cache::_getClassLocator(),
						'_ref' => 'cache_success_marker',
						'action' => 'set',
						'source' => 'manual',
						'manual' => 'executed',
						'tag' => 'events.success',
					],
				],
			],
		] );

		$result = $this->executeAutomationScenario( $automation, [ 'id' => 'CASE-1' ] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'processed', $result['data']['status'] );
		$this->assertSame( 'executed', $this->getLastAutomationContext()?->getCacheTag( 'events.success' ) );
	}
}
