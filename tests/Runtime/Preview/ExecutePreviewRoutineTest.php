<?php

namespace SyncEngine\Tests\Runtime\Preview;

use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\PreviewRuntimeScenarioTestCase;

class ExecutePreviewRoutineTest extends PreviewRuntimeScenarioTestCase
{
	public function testPreviewRoutineWithSingleTask(): void
	{
		$result = $this->executePreview( 'routine', [
			'config' => [
				'_ref' => 'simple_routine',
				'tasks' => [
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'routine_task',
						'params' => [
							[ 'key' => 'routine_key', 'value' => 'routine_value' ],
						],
					],
				],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertArrayHasKey( 'Return', $result['data'] );
		$this->assertSame( 'routine_value', $result['data']['Return']['routine_key'] );
	}

	public function testPreviewRoutineWithMultipleTasks(): void
	{
		$result = $this->executePreview( 'routine', [
			'config' => [
				'_ref' => 'multi_task_routine',
				'tasks' => [
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'first_task',
						'params' => [
							[ 'key' => 'step_one', 'value' => 'done' ],
						],
					],
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'second_task',
						'params' => [
							[ 'key' => 'step_two', 'value' => '{{ data.step_one }} confirmed' ],
						],
					],
				],
			],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'done', $result['data']['Return']['step_one'] );
		$this->assertSame( 'done confirmed', $result['data']['Return']['step_two'] );
	}

	public function testPreviewRoutineWithConditions(): void
	{
		$result = $this->executePreview( 'routine', [
			'config' => [
				'_ref' => 'conditional_routine',
				'conditions' => [
					[ 'key' => 'should_run', 'operator' => '==', 'compare' => 'true' ],
				],
				'tasks' => [
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'conditional_task',
						'params' => [
							[ 'key' => 'conditional_result', 'value' => 'executed' ],
						],
					],
				],
			],
			'data' => [ 'should_run' => 'true' ],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'executed', $result['data']['Return']['conditional_result'] );
	}

	public function testPreviewRoutineWithConditionsSkipped(): void
	{
		$result = $this->executePreview( 'routine', [
			'config' => [
				'_ref' => 'skip_routine',
				'conditions' => [
					[ 'key' => 'should_run', 'operator' => '==', 'compare' => 'true' ],
				],
				'tasks' => [
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'should_not_run',
						'params' => [
							[ 'key' => 'result', 'value' => 'executed' ],
						],
					],
				],
			],
			'data' => [ 'should_run' => 'false' ],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertArrayNotHasKey( 'result', $result['data']['Return'] );
	}

	public function testPreviewRoutineWithInputDataFlow(): void
	{
		$result = $this->executePreview( 'routine', [
			'config' => [
				'_ref' => 'flow_routine',
				'tasks' => [
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'task_one',
						'params' => [
							[ 'key' => 'original', 'value' => '{{ data.original }}' ],
						],
					],
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'task_two',
						'params' => [
							[ 'key' => 'derived', 'value' => '{{ data.original }}_derived' ],
						],
					],
				],
			],
			'data' => [ 'original' => 'base' ],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'base', $result['data']['Return']['original'] );
		$this->assertSame( 'base_derived', $result['data']['Return']['derived'] );
	}
}
