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
			'rel' => '1,3,5',
		];

		$config = [
			'key' => 'rel',
			'separator' => ',',
		];

		// Default (value).

		$expected = [
			'name' => 'Test',
			'rel' => [ '1', '3', '5' ],
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Custom separator;

		$data['rel'] = '1;5;9';
		$config['separator'] = ';';

		$expected = [
			'name' => 'Test',
			'rel' => [ '1', '5', '9' ],
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Restore data.

		$data['rel'] = '1,3,5';
		$config['separator'] = ',';

		// Split indexed default.

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
			'prefix_foo__0' => '1',
			'prefix_foo__1' => '3',
			'prefix_foo__2' => '5',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Custom index start with indexed template.

		$config['index_start'] = 2;
		$config['index_key'] = 'prefix_{%key%}__{%index%}';

		$expected = [
			'name' => 'Test',
			'prefix_rel__2' => '1',
			'prefix_rel__3' => '3',
			'prefix_rel__4' => '5',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testSplitRecursive(): void
	{
		$task = $this->getTask();

		$data = [
			'name' => 'Test',
			'foo' => [
				'name' => 'Test',
				'bar' => [
					'rel' => '1,3,5',
				],
			],
		];

		$config = [
			'key' => 'foo.bar.rel',
			'separator' => ',',
		];

		// Default (value).

		$expected = [
			'name' => 'Test',
			'foo' => [
				'name' => 'Test',
				'bar' => [
					'rel' => [ '1', '3', '5' ],
				],
			],
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed default + remove original.

		$config['action'] = 'indexed';
		$config['remove'] = false;

		$expected = [
			'name' => 'Test',
			'foo' => [
				'name' => 'Test',
				'bar' => [
					'rel' => '1,3,5',
					'rel_0' => '1',
					'rel_1' => '3',
					'rel_2' => '5',
				],
			],
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed default traversed + remove original.

		$config['action']    = 'indexed';
		$config['remove']    = true;
		$config['index_key'] = 'bar.rel_{%index%}';

		$expected = [
			'name' => 'Test',
			'foo' => [
				'name' => 'Test',
				'bar' => [],
			],
			'bar' => [
				'rel_0' => '1',
				'rel_1' => '3',
				'rel_2' => '5',
			],
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// @todo Loop key + indexed template.

		/*
		$config['key']       = 'foo.bar.[].rel';
		$config['action']    = 'indexed';
		$config['remove']    = true;
		$config['index_key'] = 'prefix_rel__{%index%}';

		$data = [
			'name' => 'Test',
			'foo' => [
				'name' => 'Test',
				'bar' => [
					2 => [
						'rel' => '1,4,7',
					],
					5 => [
						'rel' => '2,5,8',
					],
					6 => [
						'rel' => '3,6,9',
					]
				],
			],
		];

		$expected = [
			'name' => 'Test',
			'foo' => [
				'name' => 'Test',
				'bar' => [
					2 => [
						'prefix_rel__0' => '1',
						'prefix_rel__1' => '4',
						'prefix_rel__2' => '7',
					],
					5 => [
						'prefix_rel__0' => '2',
						'prefix_rel__1' => '5',
						'prefix_rel__2' => '8',
					],
					6 => [
						'prefix_rel__0' => '3',
						'prefix_rel__1' => '6',
						'prefix_rel__2' => '9',
					]
				],
			],
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
		*/
	}
}
