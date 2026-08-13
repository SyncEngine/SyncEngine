<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class FilterTest extends TaskTestCase
{
	protected string $_task = 'Filter';

	public function testFilter(): void
	{
		$context = $this->getContext();

		// NOTE THAT THIS ARRAY IS NOT A LIST (5th item).
		$data = [
			0  => [
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
			],
			1  => [
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
			],
			2  => [
				'title' => 'Test 3.',
				'sku'   => '',
			],
			3  => [
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			50 => [ // NOT A LIST!
				'title' => 'Bar 5.',
				'sku'   => '1',
			],
		];

		$config = [
			'key'        => '',
			'method'     => 'valid',
			'conditions' => [
				[
					'key' => 'sku',
					'operator' => 'contains',
					'compare' => '234',
				]
			],
		];

		$expected = [
			0 => [
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
			],
			1 => [
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
			],
		];

		$returnData = $this->execute( $config, $context, $data );
		$this->assertEquals( $expected, $returnData );

		// Expect the same result with a forced list.
		$returnData = $this->execute( $config, $context, array_values( $data ) );
		$this->assertEquals( array_values( $expected ), $returnData );

		/**
		 * Filter by invalid results.
		 */

		$config = [
			'key'        => '',
			'method'     => 'invalid',
			'conditions' => [
				[
					'key' => 'sku',
					'operator' => 'contains',
					'compare' => '1',
				]
			],
		];

		// Note the key indexes, these stay the same.
		$expected = [
			2 => [
				'title' => 'Test 3.',
				'sku'   => '',
			],
			3 => [
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
		];

		$returnData = $this->execute( $config, $context, $data );
		$this->assertEquals( $expected, $returnData );

		// Expect the same result with a forced list.
		$returnData = $this->execute( $config, $context, array_values( $data ) );
		$this->assertEquals( array_values( $expected ), $returnData );

		/**
		 * Filter no-empty.
		 */

		$config = [
			'key'        => '',
			'method'     => 'valid',
			'conditions' => [
				[
					'operator' => 'not_empty',
				]
			],
		];

		$data = [
			null,
			false,
			1,
			2,
			50
		];

		$expected = [
			1,
			2,
			50
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );
	}

	public function testWildcardKey(): void
	{
		$context = $this->getContext();

		$data = [
			'alpha' => [ 'name' => 'alpha' ],
			'beta'  => [ 'name' => 'beta' ],
			'gamma' => [ 'name' => 'gamma' ],
		];

		$config = [
			'key'        => '',
			'method'     => 'valid',
			'conditions' => [
				[
					'key'      => 'name',
					'operator' => 'eq',
					'compare'  => '{*key*}',
				]
			],
		];

		$expected = [
			'alpha' => [ 'name' => 'alpha' ],
			'beta'  => [ 'name' => 'beta' ],
			'gamma' => [ 'name' => 'gamma' ],
		];

		$returnData = $this->execute( $config, $context, $data );
		$this->assertEquals( $expected, $returnData );

		$config = [
			'key'        => '',
			'method'     => 'valid',
			'conditions' => [
				[
					'source'   => '{*key*}',
					'operator' => 'eq',
					'compare'  => 'gamma',
				]
			],
		];

		$expected = [
			'gamma' => [ 'name' => 'gamma' ],
		];

		$returnData = $this->execute( $config, $context, $data );
		$this->assertEquals( $expected, $returnData );
	}

	public function testWildcardIndex(): void
	{
		$context = $this->getContext();

		$data = [
			[ 'id' => 0, 'name' => 'First' ],
			[ 'id' => 1, 'name' => 'Second' ],
			[ 'id' => 2, 'name' => 'Third' ],
		];

		$config = [
			'key'        => '',
			'method'     => 'valid',
			'conditions' => [
				[
					'key'      => 'id',
					'operator' => 'eq',
					'compare'  => '{*index*}',
				]
			],
		];

		$expected = [
			[ 'id' => 0, 'name' => 'First' ],
			[ 'id' => 1, 'name' => 'Second' ],
			[ 'id' => 2, 'name' => 'Third' ],
		];

		$returnData = $this->execute( $config, $context, $data );
		$this->assertEquals( $expected, $returnData );

		$config = [
			'key'        => '',
			'method'     => 'valid',
			'conditions' => [
				[
					'source'   => '{*index*}',
					'operator' => 'eq',
					'compare'  => '1',
				]
			],
		];

		$expected = [
			[ 'id' => 1, 'name' => 'Second' ],
		];

		$returnData = $this->execute( $config, $context, $data );
		$this->assertEquals( $expected, $returnData );
	}

	public function testWildcardCurrent(): void
	{
		$context = $this->getContext();

		$data = [
			[ 'id' => 1, 'name' => 'First' ],
			[ 'id' => 2, 'name' => 'Second' ],
			[ 'id' => 3, 'name' => 'Third' ],
		];

		$config = [
			'key'        => '',
			'method'     => 'valid',
			'conditions' => [
				[
					'key'      => 'id',
					'operator' => 'eq',
					'compare'  => '{*current*}',
				]
			],
		];

		$expected = [
			[ 'id' => 1, 'name' => 'First' ],
			[ 'id' => 2, 'name' => 'Second' ],
			[ 'id' => 3, 'name' => 'Third' ],
		];

		$returnData = $this->execute( $config, $context, $data );
		$this->assertEquals( $expected, $returnData );

		$config = [
			'key'        => '',
			'method'     => 'valid',
			'conditions' => [
				[
					'source'   => '{*current*}',
					'operator' => 'eq',
					'compare'  => '3',
				]
			],
		];

		$expected = [
			[ 'id' => 3, 'name' => 'Third' ],
		];

		$returnData = $this->execute( $config, $context, $data );
		$this->assertEquals( $expected, $returnData );
	}
}
