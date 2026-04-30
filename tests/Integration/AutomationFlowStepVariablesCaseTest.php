<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationFlowStepVariablesCaseTest extends AutomationScenarioTestCase
{
	public function testFlowStepInputAndVariablesAreResolvedAcrossSteps(): void
	{
		$composeGreeting = $this->createRoutineScenario( 'Compose Greeting Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'compose_greeting',
				'params' => [
					[
						'key' => 'greeting',
						'value' => '{{ variables.salutation }} {{ data.name }}',
					],
				],
			],
		] );

		$finalizeMessage = $this->createRoutineScenario( 'Finalize Message Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'finalize_message',
				'params' => [
					[
						'key' => 'summary',
						'value' => '{{ data.greeting }} via {{ variables.channel }}',
					],
				],
			],
		] );

		$flow = $this->createFlowScenario( 'Step Variables Flow', [
			[
				'_ref' => 'greet',
				'routine' => $composeGreeting->getId(),
				'config' => [
					'variables' => [
						'salutation' => 'Hello',
					],
				],
			],
			[
				'_ref' => 'finalize',
				'routine' => $finalizeMessage->getId(),
				'config' => [
					'input' => [
						'greeting' => '{{ step.greet.greeting }}',
					],
					'variables' => [
						'channel' => 'email',
					],
				],
			],
		] );

		$automation = $this->createAutomationScenario( 'Flow Step Variables Automation', [
			'source' => [ 'request' ],
			'actions' => 'flow',
			'flow' => $flow->getId(),
		] );

		$result = $this->executeAutomationScenario( $automation, [ 'name' => 'Ada' ] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'Hello Ada', $result['data']['greeting'] );
		$this->assertSame( 'Hello Ada via email', $result['data']['summary'] );
	}
}
