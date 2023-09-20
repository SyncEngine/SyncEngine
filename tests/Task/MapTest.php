<?php

namespace App\Tests\Task;

class MapTest extends TaskTestCase
{
	protected string $_task = 'Map';

	public function testMapKey(): void
	{
		$this->bootstrap();
		$task = $this->getTask();

		$data = [
			'name' => 'Test',
			'price' => 12.34,
		];

		$config = [
			'action' => 'key',
			'map' => [
				[
					'source' => 'price',
					'target' => 'new_price',
				]
			],
		];

		// Default.

		$expected = [
			'name' => 'Test',
			'price' => 12.34,
			'new_price' => 12.34,
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Remove old keys.

		$config['remove_keys'] = true;

		$expected = [
			'name' => 'Test',
			'new_price' => 12.34,
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Mapped only

		$config['mapped_only'] = true;

		$expected = [
			'new_price' => 12.34,
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}
}
