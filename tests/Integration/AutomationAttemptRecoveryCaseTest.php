<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Task\Attempt;
use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationAttemptRecoveryCaseTest extends AutomationScenarioTestCase
{
	public function testAttemptRecoversFromInnerTaskFailure(): void
	{
		$routine = $this->createRoutineScenario( 'Attempt Recovery Routine', [
			[
				'_class' => Attempt::_getClassLocator(),
				'_ref' => 'attempt_recovery',
				'actions' => [
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'mutate_before_failure',
						'params' => [
							[
								'key' => 'foo',
								'value' => 'mutated',
							],
						],
					],
					[
						'_class' => 'Send',
						'_ref' => 'failing_send',
						'connection' => [
							'_class' => 'MissingWebservice',
						],
					],
				],
				'catch' => [
					[
						'actions' => [
							[
								'_class' => Set::_getClassLocator(),
								'_ref' => 'recover_with_original_data',
								'params' => [
									[
										'key' => 'recovered',
										'value' => '{{ data.foo }}',
									],
								],
							],
						],
					],
				],
			],
		] );

		$automation = $this->createAutomationScenario( 'Attempt Recovery Automation', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $routine->getId(),
		] );

		$result = $this->executeAutomationScenario( $automation, [
			'foo' => 'original',
			'other' => 'value',
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'original', $result['data']['foo'] );
		$this->assertSame( 'original', $result['data']['recovered'] );
		$this->assertSame( 'value', $result['data']['other'] );
	}
}
