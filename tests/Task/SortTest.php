<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class SortTest extends TaskTestCase
{
	protected string $_task = 'Sort';

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
