<?php

namespace App\Tests\Task;

class ReplaceTest extends TaskTestCase
{
	protected string $_task = 'Replace';

	public function testReplace(): void
	{
		$this->bootstrap();
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
}
