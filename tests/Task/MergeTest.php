<?php

namespace App\Tests\Task;

use App\Tests\TestCase\TaskTestCase;

class MergeTest extends TaskTestCase
{
	protected string $_task = 'Merge';

	public function testSplit(): void
	{
		$task = $this->getTask();

		$data = [
			'name' => 'Test',
			'rel' => [ '1', '3', '5' ],
		];

		$config = [
			'key' => 'rel',
			'separator' => ',',
		];

		// Default (value).

		$expected = [
			'name' => 'Test',
			'rel' => '1,3,5',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Custom separator;

		$config['separator'] = ';';

		$expected = [
			'name' => 'Test',
			'rel' => '1;3;5',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Set indexed data.
		$data = [
			'name' => 'Test',
			'rel_0' => '1',
			'rel_1' => '3',
			'rel_2' => '5',
		];

		// Split indexed default.

		$config['separator'] = ',';
		$config['action'] = 'indexed';

		$expected = [
			'name' => 'Test',
			'rel' => '1,3,5',
			'rel_0' => '1',
			'rel_1' => '3',
			'rel_2' => '5',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed default + remove original.

		$config['action'] = 'indexed';
		$config['remove'] = true;

		$expected = [
			'name' => 'Test',
			'rel' => '1,3,5',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed template.

		$data = [
			'name' => 'Test',
			'prefix_rel__0' => '1',
			'prefix_rel__1' => '3',
			'prefix_rel__2' => '5',
		];
		$config['index_key'] = 'prefix_{%key%}__{%index%}';

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed template new key.

		$data = [
			'name' => 'Test',
			'prefix_foo__0' => '1',
			'prefix_foo__1' => '3',
			'prefix_foo__2' => '5',
		];
		$config['index_key'] = 'prefix_foo__{%index%}';

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}
}
