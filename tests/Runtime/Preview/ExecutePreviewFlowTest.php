<?php

namespace SyncEngine\Tests\Runtime\Preview;

use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\PreviewRuntimeScenarioTestCase;

class ExecutePreviewFlowTest extends PreviewRuntimeScenarioTestCase
{
	public function testPreviewFlowWithSteps(): void
	{
		$routine = $this->createRoutineScenario( 'Flow Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'flow_task',
				'params' => [
					[ 'key' => 'flow_key', 'value' => 'flow_value' ],
				],
			],
		] );

		$result = $this->executePreview( 'flow', [
			'config' => [
				'_ref' => 'test_flow',
				'steps' => [
					[
						'_ref' => 'flow_step',
						'routine' => $routine->getId(),
					],
				],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'flow_value', $result['data']['Return']['flow_key'] );
	}

	public function testPreviewFlowWithMultipleSteps(): void
	{
		$routineOne = $this->createRoutineScenario( 'Step One Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'first',
				'params' => [
					[ 'key' => 'step', 'value' => '1' ],
				],
			],
		] );

		$routineTwo = $this->createRoutineScenario( 'Step Two Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'second',
				'params' => [
					[ 'key' => 'step', 'value' => '2' ],
				],
			],
		] );

		$result = $this->executePreview( 'flow', [
			'config' => [
				'_ref' => 'multi_step_flow',
				'steps' => [
					[ '_ref' => 'step_one', 'routine' => $routineOne->getId() ],
					[ '_ref' => 'step_two', 'routine' => $routineTwo->getId() ],
				],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( '2', $result['data']['Return']['step'] );
	}

	public function testPreviewFlowWithStepConfigOverride(): void
	{
		$routine = $this->createRoutineScenario( 'Override Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'override_task',
				'params' => [
					[ 'key' => 'value', 'value' => '{{ data.overridden }}' ],
				],
			],
		] );

		$result = $this->executePreview( 'flow', [
			'config' => [
				'_ref' => 'override_flow',
				'steps' => [
					[
						'_ref' => 'override_step',
						'routine' => $routine->getId(),
						'config' => [
							'input' => [ 'overridden' => 'overridden_value' ],
						],
					],
				],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'overridden_value', $result['data']['Return']['value'] );
	}

	public function testPreviewFlowPreservesStepData(): void
	{
		$firstRoutine = $this->createRoutineScenario( 'First Step Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'first_var_task',
				'params' => [
					[ 'key' => 'first_step_key', 'value' => '{{ data.input_key }} parsed by the first step routine' ],
				],
			],
		] );

		$secondRoutine = $this->createRoutineScenario( 'Second Step Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'second_var_task',
				'params' => [
					[ 'key' => 'second_step_key', 'value' => '{{ data.input_key }} and extended by the second step routine' ],
				],
			],
		] );

		$result = $this->executePreview( 'flow', [
			'config' => [
				'_ref' => 'var_flow',
				'steps' => [
					[
						'_ref' => 'first_step',
						'routine' => $firstRoutine->getId(),
						'config' => [
							'input' => [ 'input_key' => 'This is a test value' ],
						],
					],
					[
						'_ref' => 'second_step',
						'routine' => $secondRoutine->getId(),
						'config' => [
							'input' => [ 'input_key' => '{{ step.first_step.first_step_key }}' ],
						],
					],
				],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame(
			'This is a test value parsed by the first step routine and extended by the second step routine',
			$result['data']['Return']['second_step_key']
		);
	}

	public function testPreviewFlowWithEmptySteps(): void
	{
		$result = $this->executePreview( 'flow', [
			'config' => [
				'_ref' => 'empty_flow',
				'steps' => [],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
	}

	public function testPreviewFlowWithStepInputMapping(): void
	{
		$routine = $this->createRoutineScenario( 'Mapping Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'map_task',
				'params' => [
					[ 'key' => 'mapped', 'value' => '{{ data.mapped }}' ],
				],
			],
		] );

		$result = $this->executePreview( 'flow', [
			'config' => [
				'_ref' => 'mapping_flow',
				'steps' => [
					[
						'_ref' => 'mapped_step',
						'routine' => $routine->getId(),
						'config' => [
							'input' => [ 'mapped' => 'mapped_value' ],
						],
					],
				],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'mapped_value', $result['data']['Return']['mapped'] );
	}
}
