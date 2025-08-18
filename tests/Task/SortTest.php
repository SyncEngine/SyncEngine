<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class SortTest extends TaskTestCase
{
	protected string $_task = 'Sort';

	public function testSortByValue(): void
	{
		$context = $this->getContext();

		$data = [
			'list' => [
				'Test 1.',
				'Cheese 2.',
				'Foo 3.',
				'Bar 4.',
				'Sort 5.',
			],
		];

		$config = [
			'key' => 'list',
			'method' => 'value',
			'sort_order' => 'ASC',
		];

		$expected = [
			'list' => [
				'Bar 4.',
				'Cheese 2.',
				'Foo 3.',
				'Sort 5.',
				'Test 1.',
			],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );

		$config['sort_order'] = 'DESC';

		$expected = [
			'list' => [
				'Test 1.',
				'Sort 5.',
				'Foo 3.',
				'Cheese 2.',
				'Bar 4.',
			],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );

		// Numeric sort.

		$data = [
			'list' => [
				't3',
				't2',
				't10',
				't1',
				't11',
				't20'
			],
		];

		$config['sort_order'] = 'NASC';

		$expected = [
			'list' => [
				't1',
				't2',
				't3',
				't10',
				't11',
				't20',
			]
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );
	}

	public function testSortColumnList(): void
	{
		$context = $this->getContext();

		$data = [
			[
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
				'parent' => 'one',
			],
			[
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
				'parent' => 'two',
			],
			[
				'title' => 'Test 3.',
				'sku'   => '',
				'parent' => 'one',
			],
			[
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			[
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
			[
				'title' => 'Bar 5.',
				'sku'   => '1',
				'parent' => 'two',
			],
			[
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			[
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
				'parent' => 'one',
			],
			[
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
				'parent' => 'two',
			],
			[
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
			[
				'title' => 'Test 3.',
				'sku'   => '',
				'parent' => 'one',
			],
			[
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
				'parent' => 'two',
			],
			[
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
				'parent' => 'one',
			],
			[
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			[
				'title' => 'Bar 5.',
				'sku'   => '1',
				'parent' => 'two',
			],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );
	}

	public function testSortColumnAssoc(): void
	{
		$context = $this->getContext();

		$data = [
			'_0' => [
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
				'parent' => 'one',
			],
			'_1' => [
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
				'parent' => 'two',
			],
			'_2' => [
				'title' => 'Test 3.',
				'sku'   => '',
				'parent' => 'one',
			],
			'_3' => [
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			'_4' => [
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
			'_4' => [
				'title' => 'Bar 5.',
				'sku'   => '1',
				'parent' => 'two',
			],
			'_3' => [
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			'_0' => [
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
				'parent' => 'one',
			],
			'_1' => [
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
				'parent' => 'two',
			],
			'_2' => [
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
			'_2' => [
				'title' => 'Test 3.',
				'sku'   => '',
				'parent' => 'one',
			],
			'_1' => [
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
				'parent' => 'two',
			],
			'_0' => [
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
				'parent' => 'one',
			],
			'_3' => [
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			'_4' => [
				'title' => 'Bar 5.',
				'sku'   => '1',
				'parent' => 'two',
			],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );
	}
}
