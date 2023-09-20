<?php

namespace App\Tests\Task;

use App\Tests\TestCase\TaskTestCase;

class ReplaceTest extends TaskTestCase
{
	protected string $_task = 'Replace';

	public function testReplace(): void
	{
		$task = $this->getTask();

		$data = [
			'name' => 'Test',
			'price' => 12.34,
			'test' => 'value',
		];

		$config = [
			'params' => [
				[
					'find' => 'est',
					'replace' => 'esting',
				]
			],
		];

		// Default (value).

		$expected = [
			'name' => 'Testing',
			'price' => 12.34,
			'test' => 'value',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Replace Key.

		$config['action'] = 'key';

		$expected = [
			'name' => 'Test',
			'price' => 12.34,
			'testing' => 'value',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Replace Both.

		$config['action'] = 'both';

		$expected = [
			'name' => 'Testing',
			'price' => 12.34,
			'testing' => 'value',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testReplaceRecursive(): void
	{
		$task = $this->getTask();

		$data = [
			'name' => 'Test',
			'price' => 12.34,
			'test' => [
				'name' => 'Test',
				'test' => [
					'name' => 'Test',
					'test' => 'value',
				],
			],
		];

		$config = [
			'recursive' => true,
			'params' => [
				[
					'find' => 'est',
					'replace' => 'esting',
				]
			],
		];

		// Default (value).

		$expected = [
			'name' => 'Testing',
			'price' => 12.34,
			'test' => [
				'name' => 'Testing',
				'test' => [
					'name' => 'Testing',
					'test' => 'value',
				],
			],
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Replace Key.

		$config['action'] = 'key';

		$expected = [
			'name' => 'Test',
			'price' => 12.34,
			'testing' => [
				'name' => 'Test',
				'testing' => [
					'name' => 'Test',
					'testing' => 'value',
				],
			],
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Replace Both.

		$config['action'] = 'both';

		$expected = [
			'name' => 'Testing',
			'price' => 12.34,
			'testing' => [
				'name' => 'Testing',
				'testing' => [
					'name' => 'Testing',
					'testing' => 'value',
				],
			],
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}
}
