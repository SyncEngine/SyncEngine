<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class GroupTest extends TaskTestCase
{
	protected string $_task = 'Group';

	public function testGroup(): void
	{
		$context = $this->getContext();

		$data = [
			0 => [
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
				'parent' => 'one',
			],
			1 => [
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
				'parent' => 'two',
			],
			2 => [
				'title' => 'Test 3.',
				'sku'   => '',
				'parent' => 'one',
			],
			3 => [
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			4 => [
				'title' => 'Bar 5.',
				'sku'   => '1',
				'parent' => 'two',
			],
		];

		$config = [
			'group_by' => 'parent',
			'default_group' => 'none',
		];

		$expected = [
			'one' => [
				0 => [
					'title' => 'Test 1.',
					'sku'   => 'ABCD.001/23456',
					'parent' => 'one',
				],
				2 => [
					'title' => 'Test 3.',
					'sku'   => '',
					'parent' => 'one',
				],
			],
			'two' => [
				1 => [
					'title' => 'Test 2.',
					'sku'   => '23456.ABCD.001',
					'parent' => 'two',
				],
				4 => [
					'title' => 'Bar 5.',
					'sku'   => '1',
					'parent' => 'two',
				],
			],
			'none' => [
				3 => [
					'title' => 'Foo 4.',
					'sku'   => '0',
				],
			],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );

		$config = [
			'group_by' => 'parent',
			'default_group' => '',
		];

		$expected = [
			'one' => [
				0 => [
					'title' => 'Test 1.',
					'sku'   => 'ABCD.001/23456',
					'parent' => 'one',
				],
				2 => [
					'title' => 'Test 3.',
					'sku'   => '',
					'parent' => 'one',
				],
			],
			'two' => [
				1 => [
					'title' => 'Test 2.',
					'sku'   => '23456.ABCD.001',
					'parent' => 'two',
				],
				4 => [
					'title' => 'Bar 5.',
					'sku'   => '1',
					'parent' => 'two',
				],
			],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );
	}
}
