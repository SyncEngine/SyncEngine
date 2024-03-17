<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Task\Map;
use SyncEngine\Tests\TestCase\TaskTestCase;

class MapTest extends TaskTestCase
{
	protected string $_task = 'Map';

	public function testMapKey(): void
	{
		$data = [
			'name' => 'Test',
			'price' => 12.34,
		];

		$config = [
			'action' => 'key',
			'map' => [
				'manual' => [
					[
						'source' => 'price',
						'target' => 'new_price',
					],
				],
			],
		];

		// Default.

		$expected = [
			'name' => 'Test',
			'price' => 12.34,
			'new_price' => 12.34,
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Remove old keys.

		$config['remove_keys'] = true;

		$expected = [
			'name' => 'Test',
			'new_price' => 12.34,
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Mapped only

		$config['mapped_only'] = true;

		$expected = [
			'new_price' => 12.34,
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Nested arrays.

		$config['map'] = [
			'manual' => [
				[
					'source' => 'relationships.list.ids',
					'target' => 'relations',
				],
			],
		];
		$data = [
			'relationships' => [
				'list' => [
					'ids' => [ 1, 2, 3 ],
				],
			],
		];
		$expected = [
			'relations' => [ 1, 2, 3 ],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testMapValue(): void
	{
		$data = [
			'name' => 'Test',
			'price' => 12.34,
		];

		$config = [
			'action' => 'value',
			'key' => 'name',
			'map' => [
				'manual' => [
					[
						// Only replaces exact matches. For partials, use Replace task.
						'source' => 'Test',
						'target' => 'Testing',
					],
				],
			],
		];

		// Default.

		$expected = [
			'name' => 'Testing',
			'price' => 12.34,
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Key.

		$data['groups'] = [ 'Sync', 'Motor' ];

		$config['key'] = 'groups';
		$config['map'] = [
			'manual' => [
				[
					'source' => 'Motor',
					'target' => 'Engine',
				],
			],
		];

		$expected = [
			'name' => 'Test',
			'price' => 12.34,
			'groups' => [ 'Sync', 'Engine' ],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testConvertSchema(): void
	{
		// Remove decimals and convert type.
		$value = 12.34;

		$targetSchema = [
			'_class' => 'Numeric',
			'type' => 'format',
			'decimals' => 1,
			'decimal_separator' => ',',
			'thousands_separator' => '.',
		];

		/** @var Map $task */
		$task = TaskModel::get( $this->_task );

		$formatted = $task->convertSchema( $value, $targetSchema );

		$this->assertEquals( '12,3', $formatted );

		// Rounding up.
		$value = 12.35;

		$targetSchema = [
			'_class' => 'Numeric',
			'type' => 'format',
			'decimals' => 1,
			'decimal_separator' => ',',
			'thousands_separator' => '.',
		];

		/** @var Map $task */
		$task = TaskModel::get( $this->_task );

		$formatted = $task->convertSchema( $value, $targetSchema );

		$this->assertEquals( '12,4', $formatted );

		// Rounding down.
		$value = 12.35;

		$targetSchema = [
			'_class' => 'Numeric',
			'type' => 'format',
			'round' => 'down',
			'decimals' => 1,
			'decimal_separator' => ',',
			'thousands_separator' => '.',
		];

		/** @var Map $task */
		$task = TaskModel::get( $this->_task );

		$formatted = $task->convertSchema( $value, $targetSchema );

		$this->assertEquals( '12,3', $formatted );

		// Convert schema + round down.
		$value = '12.356,125';

		$targetSchema = [
			'_class' => 'Numeric',
			'type' => 'raw',
			'round' => 'down',
			'decimals' => 2,
		];

		/** @var Map $task */
		$task = TaskModel::get( $this->_task );

		$formatted = $task->convertSchema( $value, $targetSchema );

		$this->assertEquals( 12356.12, $formatted );

		// Convert schema + detect decimals
		$value = '12,356,-';

		$targetSchema = [
			'_class' => 'Numeric',
			'type' => 'format',
			'round' => 'down',
			'decimals' => 1,
			'decimal_separator' => '.',
			'thousands_separator' => ' ',
		];

		/** @var Map $task */
		$task = TaskModel::get( $this->_task );

		$formatted = $task->convertSchema( $value, $targetSchema );

		$this->assertEquals( '12 356.0', $formatted );
	}
}
