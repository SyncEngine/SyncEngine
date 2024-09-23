<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class MergeTest extends TaskTestCase
{
	protected string $_task = 'Merge';

	public function testMergeValues(): void
	{
		$data = [
			'name' => 'Test',
			'rel'  => [ '1', '3', '5' ],
		];

		$config = [
			'key'       => 'rel',
			'separator' => ',',
		];

		// Default (value).

		$expected = [
			'name' => 'Test',
			'rel'  => '1,3,5',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Custom separator;

		$config['separator'] = ';';

		$expected = [
			'name' => 'Test',
			'rel'  => '1;3;5',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Custom separator;

		$config['separator'] = '{*nl*}';

		$expected = [
			'name' => 'Test',
			'rel'  => "1\n3\n5",
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Custom separator;

		$config['separator'] = '{*tab*}';

		$expected = [
			'name' => 'Test',
			'rel'  => "1	3	5",
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testMergeColumns(): void
	{
		$data = [
			'name'  => 'Test',
			'one'   => '1',
			'two'   => '2',
			'three' => '3',
			'four'  => '4',
			'five'  => [ 1, 2, 3, 4, 5 ],
		];

		$config = [
			'key'        => 'rel',
			'action'     => 'key',
			'key_method' => 'columns',
			'columns'    => [
				[ 'key' => 'one' ],
				[ 'key' => 'three' ],
				[ 'key' => 'five' ],
			],
			'remove'     => true,
		];

		// Default (value).

		$expected = [
			'name' => 'Test',
			'rel'  => [ '1', '3', [ 1, 2, 3, 4, 5 ] ],
			'two'  => '2',
			'four' => '4',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Default (value), keys preserved.

		$config['preserve_keys'] = true;

		$expected = [
			'name' => 'Test',
			'rel'  => [
				'one'   => '1',
				'three' => '3',
				'five'  => [ 1, 2, 3, 4, 5 ],
			],
			'two'  => '2',
			'four' => '4',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Default (value).

		$config['action']    = 'both';
		$config['separator'] = '<br>';
		$config['columns']   = [
			[ 'key' => 'one' ],
			[ 'key' => 'three' ],
			[ 'key' => 'four' ],
		];

		$expected = [
			'name' => 'Test',
			'rel'  => '1<br>3<br>4',
			'two'  => '2',
			'five' => [ 1, 2, 3, 4, 5 ],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testMergeIndexed(): void
	{
		// Set indexed data.
		$data = [
			'name'  => 'Test',
			'rel_0' => '1',
			'rel_1' => '3',
			'rel_2' => '5',
		];

		// Split indexed default.

		$config = [
			'key'        => 'rel',
			'separator'  => ',',
			'action'     => 'both',
			'key_method' => 'indexed',
		];

		$expected = [
			'name'  => 'Test',
			'rel'   => '1,3,5',
			'rel_0' => '1',
			'rel_1' => '3',
			'rel_2' => '5',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed default + remove original.

		$config['key_method'] = 'indexed';
		$config['remove']     = true;

		$expected = [
			'name' => 'Test',
			'rel'  => '1,3,5',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed template.

		$data                = [
			'name'          => 'Test',
			'prefix_rel__0' => '1',
			'prefix_rel__1' => '3',
			'prefix_rel__2' => '5',
		];
		$config['index_key'] = 'prefix_{*key*}__{*index*}';

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed template new key.

		$data                = [
			'name'          => 'Test',
			'prefix_foo__0' => '1',
			'prefix_foo__1' => '3',
			'prefix_foo__2' => '5',
		];
		$config['index_key'] = 'prefix_foo__{*index*}';

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Custom index start with indexed template.

		$config['index_start'] = 2;
		$config['index_key']   = 'prefix_{*key*}__{*index*}';

		$data = [
			'name'          => 'Test',
			'prefix_rel__2' => '1',
			'prefix_rel__3' => '3',
			'prefix_rel__4' => '5',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testMergeCollections(): void
	{
		$data = [
			'name' => 'Test',
			'foo'  => [ 1, 2, 3 ],
			'bar'  => [ 4, 5, 6 ],
		];

		/**
		 * Merge list.
		 */

		$config = [
			'key'          => 'foo',
			'action'       => 'key',
			'key_method'   => 'columns',
			'remove'       => true,
			'columns'      => [
				[
					'key' => 'foo',
				],
				[
					'key' => 'bar',
				],
			],
			'merge_method' => 'merge',
		];

		$expected = [
			'name' => 'Test',
			'foo'  => [ 1, 2, 3, 4, 5, 6 ],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		/**
		 * Replace list.
		 */

		$config['merge_method'] = 'replace';

		$expected = [
			'name' => 'Test',
			'foo'  => [ 4, 5, 6 ],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		/**
		 * Associative lists.
		 */

		$data = [
			'name' => 'Test',
			'foo'  => [
				'one'   => 1,
				'two'   => 2,
				'three' => 3,
				0       => 'foo 0',
				1       => 'foo 1',
				2       => 'foo 2',
			],
			'bar'  => [
				'one'   => 'one',
				'two'   => 'two',
				'three' => 'three',
				'four'  => 'four',
				0       => 'bar 0',
				1       => 'bar 1',
			],
		];

		/**
		 * Insert associative list.
		 */

		$config['preserve_keys'] = false; // Default
		$config['merge_method'] = 'insert';

		$expected = [
			'name' => 'Test',
			'foo'  => [
				1,
				2,
				3,
				'foo 0',
				'foo 1',
				'foo 2',
				'four',
				'bar 0',
				'bar 1',
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		$config['preserve_keys'] = true;
		$expected = [
			'name' => 'Test',
			'foo'  => [
				'one'   => 1,
				'two'   => 2,
				'three' => 3,
				0       => 'foo 0',
				1       => 'foo 1',
				2       => 'foo 2',
				'four'  => 'four',
				3       => 'bar 0',
				4       => 'bar 1',
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		/**
		 * Merge associative list.
		 */

		$config['preserve_keys'] = false; // Default
		$config['merge_method']  = 'merge';

		$expected = [
			'name' => 'Test',
			'foo'  => [
				'one',
				'two',
				'three',
				'foo 0',
				'foo 1',
				'foo 2',
				'four',
				'bar 0',
				'bar 1',
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		$config['preserve_keys'] = true;
		$expected = [
			'name' => 'Test',
			'foo'  => [
				'one'   => 'one',
				'two'   => 'two',
				'three' => 'three',
				0       => 'foo 0',
				1       => 'foo 1',
				2       => 'foo 2',
				'four'  => 'four',
				3       => 'bar 0',
				4       => 'bar 1',
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		/**
		 * Merge list different order.
		 */

		$config['preserve_keys'] = false; // Default
		$config['columns'] = [
			[
				'key' => 'bar',
			],
			[
				'key' => 'foo',
			],
		];

		$expected = [
			'name' => 'Test',
			'foo'  => [
				1,
				2,
				3,
				'four',
				'bar 0',
				'bar 1',
				'foo 0',
				'foo 1',
				'foo 2',
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		$config['preserve_keys'] = true;
		$expected = [
			'name' => 'Test',
			'foo'  => [
				'one'   => 1,
				'two'   => 2,
				'three' => 3,
				'four'  => 'four',
				0       => 'bar 0',
				1       => 'bar 1',
				2       => 'foo 0',
				3       => 'foo 1',
				4       => 'foo 2',
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		/**
		 * Replace list foo => bar.
		 */

		$config['preserve_keys'] = false; // Default
		$config['merge_method']  = 'replace';
		$config['columns'] = [
			[
				'key' => 'foo',
			],
			[
				'key' => 'bar',
			],
		];

		$expected = [
			'name' => 'Test',
			'foo'  => [
				'one',
				'two',
				'three',
				'bar 0',
				'bar 1',
				'foo 2',
				'four',
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		$config['preserve_keys'] = true;
		$expected = [
			'name' => 'Test',
			'foo'  => [
				'one'   => 'one',
				'two'   => 'two',
				'three' => 'three',
				'four'  => 'four',
				0       => 'bar 0',
				1       => 'bar 1',
				2       => 'foo 2',
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		/**
		 * Replace list bar => foo.
		 */

		$config['preserve_keys'] = false; // Default.
		$config['merge_method']  = 'replace';
		$config['columns']       = [
			[
				'key' => 'bar',
			],
			[
				'key' => 'foo',
			],
		];

		$expected = [
			'name' => 'Test',
			'foo'  => [
				1,
				2,
				3,
				'four',
				'foo 0',
				'foo 1',
				'foo 2',
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		$config['preserve_keys'] = true;
		$expected = [
			'name' => 'Test',
			'foo'  => [
				'one'   => 1,
				'two'   => 2,
				'three' => 3,
				0       => 'foo 0',
				1       => 'foo 1',
				2       => 'foo 2',
				'four'  => 'four',
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testMergeRecursive(): void
	{
		$data = [
			'name' => 'Test',
			'foo'  => [
				'name' => 'Test',
				'bar'  => [
					'rel' => [ '1', '3', '5' ],
				],
			],
		];

		$config = [
			'key'       => 'foo.bar.rel',
			'separator' => ',',
		];

		// Default (value).

		$expected = [
			'name' => 'Test',
			'foo'  => [
				'name' => 'Test',
				'bar'  => [
					'rel' => '1,3,5',
				],
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed default + remove original.

		$config['action']     = 'both';
		$config['key_method'] = 'indexed';
		$config['remove']     = false;

		$data = [
			'name' => 'Test',
			'foo'  => [
				'name' => 'Test',
				'bar'  => [
					'rel_0' => '1',
					'rel_1' => '3',
					'rel_2' => '5',
				],
			],
		];

		$expected = [
			'name' => 'Test',
			'foo'  => [
				'name' => 'Test',
				'bar'  => [
					'rel'   => '1,3,5',
					'rel_0' => '1',
					'rel_1' => '3',
					'rel_2' => '5',
				],
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed default traversed + remove original.

		$config['action']     = 'both';
		$config['key_method'] = 'indexed';
		$config['remove']     = true;
		$config['index_key']  = 'bar.rel_{*index*}';

		$data = [
			'name' => 'Test',
			'foo'  => [
				'name' => 'Test',
				'bar'  => [],
			],
			'bar'  => [
				'rel_0' => '1',
				'rel_1' => '3',
				'rel_2' => '5',
			],
		];

		$expected = [
			'name' => 'Test',
			'foo'  => [
				'name' => 'Test',
				'bar'  => [
					'rel' => '1,3,5',
				],
			],
			'bar'  => [],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// @todo Loop key + indexed template.

		/*
		$config['key']       = 'foo.bar.[].rel';
		$config['action']    = 'indexed';
		$config['remove']    = true;
		$config['index_key'] = 'prefix_rel__{*index*}';

		$data = [
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

		$expected = [
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

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
		*/
	}
}
