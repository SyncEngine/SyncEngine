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

		$this->assertEquals( 'routine2', $result->get('done') );
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
							'key' => 'done',
							'value' => '{{ data.routine2 ?? "routine1" }}',
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
							'key' => 'routine2',
							'value' => 'routine2',
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
