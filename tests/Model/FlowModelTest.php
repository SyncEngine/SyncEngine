<?php

namespace SyncEngine\Tests\Model;


use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Tests\TestCase\ExecuteTestCase;

class FlowModelTest extends ExecuteTestCase
{
	public function testSequenceBasic(): void
	{
		$flow = FlowModel::create();
		/** @var FlowModel $flow */
		$flow->setName('Test Basic Flow');

		$routines = $this->getRoutines();

		$flow->setConfig([
			'steps' => [
				$routines['routine1']->getId(),
				$routines['routine2']->getId(),
				$routines['routine1']->getId(),
			],
		]);

		$result = $this->getExecute()->executeFlow( $flow, $this->getContext(), ExecuteData::create() );

		var_dump( $result->normalize());

		$this->assertEquals( 'routine_2', $result->get('done') );
	}

	public function testSequenceConfig(): void
	{
		$flow = FlowModel::create();
		/** @var FlowModel $flow */
		$flow->setName('Test Config Flow');

		$routines = $this->getRoutines();

		$flow->setConfig([
			'steps' => [
				[
					'routine' => $routines['routine1']->getId(),
					'config' => [],
				],
				[
					'routine' => $routines['routine2']->getId(),
					'config' => [],
				],
				[
					'routine' => $routines['routine1']->getId(),
					'config' => [
						'input' => [
							'current' => 'step_3_routine_1',
						]
					],
				],
			],
		]);

		$result = $this->getExecute()->executeFlow( $flow, $this->getContext(), ExecuteData::create() );

		var_dump( $result->normalize());

		$this->assertEquals( 'step_3_routine_1', $result->get('done') );
	}

	private function getRoutines(): array
	{
		static $routines = [];
		if ( ! empty( $routines ) ) {
			return $routines;
		}

		$routine1 = RoutineModel::create();
		/** @var RoutineModel $routine1 */
		$routine1->setName('Routine 1');
		$routine1->setConfig([
			'tasks' => [
				[
					'_class' => 'Set',
					'_ref' => 'task1',
					'name' => 'Task 1',
					'params' => [
						[
							'key' => 'current',
							'value' => 'routine_1',
						],
						[
							'key' => 'done',
							'value' => '{{ data.current ?? "routine_1" }}',
						]
					],
				],
			],
		]);

		$routine1->save();
		$routines['routine1'] = $routine1;

		$routine2 = RoutineModel::create();
		/** @var RoutineModel $routine2 */
		$routine2->setName('Routine 2');
		$routine2->setConfig([
			'tasks' => [
				[
					'_class' => 'Set',
					'_ref' => 'task2',
					'name' => 'Task 2',
					'params' => [
						[
							'key' => 'current',
							'value' => 'routine_2',
						]
					],
				],
			],
		]);

		$routine2->save( true );
		$routines['routine2'] = $routine2;

		return $routines;
	}

}
