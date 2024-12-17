<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Column\Numeric;
use SyncEngine\Tests\TestCase\TaskTestCase;

class SplitTest extends TaskTestCase
{
	protected string $_task = 'Split';

	public function testSplitValues(): void
	{
		$data = [
			'name' => 'Test',
			'rel'  => '1,3,5',
		];

		$config = [
			'action'    => 'value',
			'key'       => 'rel',
			'separator' => ',',
		];

		// Default (value).

		$expected = [
			'name' => 'Test',
			'rel'  => [ '1', '3', '5' ],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Custom separator;

		$data['rel']         = '1;5;9';
		$config['separator'] = ';';

		$expected = [
			'name' => 'Test',
			'rel'  => [ '1', '5', '9' ],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// New line separator;

		$data['rel']         = "One\nTwo\nThree";
		$config['separator'] = '{*nl*}';

		$expected = [
			'name' => 'Test',
			'rel'  => [ 'One', 'Two', 'Three' ],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Tab separator;

		$data['rel']         = "One	Two	Three";
		$config['separator'] = '{*tab*}';

		$expected = [
			'name' => 'Test',
			'rel'  => [ 'One', 'Two', 'Three' ],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split to column definition.

		$data = [ 'rel' => '1, 3, 5' ];

		$config = [
			'action'    => 'value',
			'key'       => 'rel',
			'separator' => ',',
			'column'    => [
				'_class' => Numeric::_getClassLocator(),
			],
		];

		$expected = [ 'rel' => [ 1, 3, 5 ] ];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testSplitColumns(): void
	{
		$data = [
			'name' => 'Test',
			'rel'  => [ '1', '3', '5' ],
		];

		$config = [
			'key'        => 'rel',
			'action'     => 'key',
			'separator'  => ',',
			'key_method' => 'columns',
			'columns'    => [
				[ 'key' => 'one' ],
				[ 'key' => 'three' ],
				[ 'key' => 'five' ],
			],
		];

		// Default (value).

		$expected = [
			'name'  => 'Test',
			'rel'   => [ '1', '3', '5' ],
			'one'   => '1',
			'three' => '3',
			'five'  => '5',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Custom indexes;

		$data['rel'] = [ 'zero', 'one', 'two', 'three', 'four', 'five' ];

		$config['columns'] = [
			[ 'index' => '1', 'key' => 'foo_1' ],
			[ 'index' => '3', 'key' => 'bar_3' ],
			[ 'index' => '5', 'key' => 'yay_5' ],
		];

		$expected = [
			'name'  => 'Test',
			'rel'   => [ 'zero', 'one', 'two', 'three', 'four', 'five' ],
			'foo_1' => 'one',
			'bar_3' => 'three',
			'yay_5' => 'five',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testSplitIndexed(): void
	{
		$data = [
			'name' => 'Test',
			'rel'  => '1,3,5',
		];

		$config = [
			'key'        => 'rel',
			'action'     => 'both',
			'separator'  => ',',
			'key_method' => 'indexed',
		];

		// Split indexed default.

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

		$config['remove'] = true;

		$expected = [
			'name'  => 'Test',
			'rel_0' => '1',
			'rel_1' => '3',
			'rel_2' => '5',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed template.

		$config['index_key'] = 'prefix_{*key*}__{*index*}';

		$expected = [
			'name'          => 'Test',
			'prefix_rel__0' => '1',
			'prefix_rel__1' => '3',
			'prefix_rel__2' => '5',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed template new key.

		$config['index_key'] = 'prefix_foo__{*index*}';

		$expected = [
			'name'          => 'Test',
			'prefix_foo__0' => '1',
			'prefix_foo__1' => '3',
			'prefix_foo__2' => '5',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Custom index start with indexed template.

		$config['index_start'] = 2;
		$config['index_key']   = 'prefix_{*key*}__{*index*}';

		$expected = [
			'name'          => 'Test',
			'prefix_rel__2' => '1',
			'prefix_rel__3' => '3',
			'prefix_rel__4' => '5',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testSplitRecursive(): void
	{
		$data = [
			'name' => 'Test',
			'foo'  => [
				'name' => 'Test',
				'bar'  => [
					'rel' => '1,3,5',
				],
			],
		];

		$config = [
			'action'    => 'value',
			'key'       => 'foo.bar.rel',
			'separator' => ',',
		];

		// Default (value).

		$expected = [
			'name' => 'Test',
			'foo'  => [
				'name' => 'Test',
				'bar'  => [
					'rel' => [ '1', '3', '5' ],
				],
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Split indexed default + remove original.

		$config['action']     = 'both';
		$config['key_method'] = 'indexed';
		$config['remove']     = false;

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

		$config['remove']    = true;
		$config['index_key'] = 'bar.rel_{*index*}';

		$expected = [
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

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
		*/
	}

	public function testRegex()
	{
		$address_line_pattern = '/\s+(?=\d+\s*[a-zA-Z\-\/]*$)/';

		$config = [
			'key'         => 'test',
			'action'      => 'value',
			'separator'   => '{*regex*}',
			'regex_split' => $address_line_pattern,
		];

		$data = [
			'test' => 'Teststreet 123',
		];

		$expected = [
			'test' => [
				'Teststreet',
				'123',
			],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		$config = [
			'key'         => 'test',
			'action'      => 'both',
			'separator'   => '{*regex*}',
			'regex_split' => $address_line_pattern,
			'key_method'  => 'columns',
			'columns'     => [
				[
					'index'  => 0,
					'key' => 'street',
				],
				[
					'index'  => 1,
					'key' => 'number',
				],
			],
		];

		$expected = [
			'test'   => 'Teststreet 123',
			'street' => 'Teststreet',
			'number' => '123',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}
}
