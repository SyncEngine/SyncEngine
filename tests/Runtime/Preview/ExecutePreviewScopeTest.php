<?php

namespace SyncEngine\Tests\Runtime\Preview;

use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\PreviewRuntimeScenarioTestCase;

class ExecutePreviewScopeTest extends PreviewRuntimeScenarioTestCase
{
	public function testPreviewScopeWithRoutineQueue(): void
	{
		$routineOne = $this->createRoutineScenario( 'Scope Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'scope_one',
				'params' => [
					[ 'key' => 'scope_step', 'value' => 'routine' ],
				],
			],
		] );

		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'scope_target',
				'params' => [
					[ 'key' => 'result', 'value' => 'final' ],
				],
			],
			'scope' => [
				[ '_entity' => 'Routine', 'id' => $routineOne->getId() ],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'routine', $result['data']['Return']['scope_step'] );
	}

	public function testPreviewScopeWithFlowQueue(): void
	{
		$routine = $this->createRoutineScenario( 'Flow Scope Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'flow_scope_task',
				'params' => [
					[ 'key' => 'flow_scope_result', 'value' => 'from_flow' ],
				],
			],
		] );

		$flow = $this->createFlowScenario( 'Flow Scope', [
			[ '_ref' => 'flow_scope_step', 'routine' => $routine->getId() ],
		] );

		$result = $this->executePreview( 'flow', [
			'config' => [
				'_ref' => 'scope_flow',
				'steps' => [
					[ '_ref' => 'scope_step', 'routine' => $routine->getId() ],
				],
			],
			'scope' => [
				[ '_entity' => 'Flow', 'id' => $flow->getId() ],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'from_flow', $result['data']['Return']['flow_scope_result'] );
	}

	public function testPreviewScopeWithAutomationQueue(): void
	{
		$routine = $this->createRoutineScenario( 'Automation Scope Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'auto_scope_task',
				'params' => [
					[ 'key' => 'auto_scope_result', 'value' => 'from_automation' ],
				],
			],
		] );

		$flow = $this->createFlowScenario( 'Automation Scope Flow', [
			[ '_ref' => 'auto_flow_step', 'routine' => $routine->getId() ],
		] );

		$automation = $this->createAutomationScenario( 'Scope Automation', [
			'source' => [ 'request' ],
			'actions' => 'flow',
			'flow' => $flow->getId(),
		] );

		$result = $this->executePreview( 'automation', [
			'config' => $automation->getConfig(),
			'data' => [ 'input' => 'scope_test' ],
			'scope' => [
				[ '_entity' => 'Automation', 'id' => $automation->getId() ],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'from_automation', $result['data']['Return']['auto_scope_result'] );
	}

	public function testPreviewScopeWithConfigOverride(): void
	{
		$routine = $this->createRoutineScenario( 'Override Scope Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'override_scope_task',
				'params' => [
					[ 'key' => 'value', 'value' => 'original' ],
				],
			],
		] );

		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'target',
				'params' => [
					[ 'key' => 'result', 'value' => 'base' ],
				],
			],
			'scope' => [
				[
					'_entity' => 'Routine',
					'id' => $routine->getId(),
					'config' => [
						'tasks' => [
							[
								'_class' => Set::_getClassLocator(),
								'_ref' => 'override_scope_task',
								'params' => [
									[ 'key' => 'value', 'value' => 'overridden' ],
								],
							]
						],
					],
				],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'overridden', $result['data']['Return']['value'] );
	}

	public function testPreviewScopeWithDataPropagated(): void
	{
		$routine = $this->createRoutineScenario( 'Data Prop Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'prop_task',
				'params' => [
					[ 'key' => 'propagated', 'value' => '{{ data.prop_input }}' ],
				],
			],
		] );

		$result = $this->executePreview( 'routine', [
			'config' => [
				'_ref' => 'prop_config',
				'tasks' => [
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'target',
						'params' => [
							[ 'key' => 'result', 'value' => 'base' ],
						],
					],
				],
			],
			'data' => [ 'prop_input' => 'propagated_value' ],
			'scope' => [
				[ '_entity' => 'Routine', 'id' => $routine->getId() ],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'propagated_value', $result['data']['Return']['propagated'] );
	}

	public function testPreviewScopeSourceReturned(): void
	{
		$routine = $this->createRoutineScenario( 'Source Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'source_task',
				'params' => [
					[ 'key' => 'source_key', 'value' => 'source_val' ],
				],
			],
		] );

		$result = $this->executePreview( 'routine', [
			'config' => [
				'_ref' => 'source_config',
				'tasks' => [
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'target',
						'params' => [
							[ 'key' => 'result', 'value' => 'base' ],
						],
					],
				],
			],
			'data' => [ 'original' => 'original_value' ],
			'scope' => [
				[ '_entity' => 'Routine', 'id' => $routine->getId() ],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertArrayHasKey( 'source', $result );
	}
}
