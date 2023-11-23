<?php

namespace App\Tests\Task;

use App\Tests\TestCase\TaskTestCase;

class SplitTest extends TaskTestCase
{
	protected string $_task = 'Split';

	public function testSplit(): void
	{
		$task = $this->getTask();

		$data = [
			'name' => 'Test',
			'price' => 12.34,
			'rel' => '1,3,5',
		];

		$config = [
			'key' => 'rel',
			'separator' => ',',
		];

		// Default (value).

		$expected = [
			'name' => 'Test',
			'price' => 12.34,
			'rel' => [ '1', '3', '5' ],
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed default.

		$config['action'] = 'indexed';

		$expected = [
			'name' => 'Test',
			'price' => 12.34,
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
			'price' => 12.34,
			'rel_0' => '1',
			'rel_1' => '3',
			'rel_2' => '5',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed template.

		$config['index_key'] = 'prefix_{%key%}__{%index%}';

		$expected = [
			'name' => 'Test',
			'price' => 12.34,
			'prefix_rel__0' => '1',
			'prefix_rel__1' => '3',
			'prefix_rel__2' => '5',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed template new key.

		$config['index_key'] = 'prefix_foo__{%index%}';

		$expected = [
			'name' => 'Test',
			'price' => 12.34,
			'prefix_foo__0' => '1',
			'prefix_foo__1' => '3',
			'prefix_foo__2' => '5',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		$config['index_start'] = 2;

		// Split indexed template.

		$config['index_key'] = 'prefix_{%key%}__{%index%}';

		$expected = [
			'name' => 'Test',
			'price' => 12.34,
			'prefix_rel__2' => '1',
			'prefix_rel__3' => '3',
			'prefix_rel__4' => '5',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}
}
