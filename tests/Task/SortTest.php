<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class SortTest extends TaskTestCase
{
	protected string $_task = 'Sort';

	public function testSortColumn(): void
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
			'method' => 'column',
			'sort_by' => 'title',
		];

		$expected = [
			'4' => [
				'title' => 'Bar 5.',
				'sku'   => '1',
				'parent' => 'two',
			],
			'3' => [
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			'0' => [
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
				'parent' => 'one',
			],
			'1' => [
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
				'parent' => 'two',
			],
			'2' => [
				'title' => 'Test 3.',
				'sku'   => '',
				'parent' => 'one',
			],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );

		$config = [
			'method' => 'column',
			'sort_by' => 'title',
			'sort_order' => 'DESC',
		];

		$expected = [
			'2' => [
				'title' => 'Test 3.',
				'sku'   => '',
				'parent' => 'one',
			],
			'1' => [
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
				'parent' => 'two',
			],
			'0' => [
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
				'parent' => 'one',
			],
			'3' => [
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			'4' => [
				'title' => 'Bar 5.',
				'sku'   => '1',
				'parent' => 'two',
			],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );
	}
}
