<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class IndexTest extends TaskTestCase
{
	protected string $_task = 'Index';

	public function testIndex(): void
	{
		$context = $this->getContext();

		$data = [
			0 => [
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
			],
			1 => [
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
			],
			'random key' => [
				'title' => 'Test 3.',
				'sku'   => '',
			],
			'foo bar' => [
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			66 => [
				'title' => 'Bar 5.',
				'sku'   => '1',
			],
		];

		$config = [
			'method'    => 'assoc',
			'index_key' => '{{ row.sku }}',
		];

		$expected = [
			'ABCD.001/23456' => [
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
			],
			'23456.ABCD.001' => [
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
			],
			'0' => [
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			'1' => [
				'title' => 'Bar 5.',
				'sku'   => '1',
			],
			'2' => [
				'title' => 'Test 3.',
				'sku'   => '',
			],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );

		/**
		 * Reindex as list.
		 */
		$config = [
			'method'    => 'list',
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
			2 => [
				'title' => 'Test 3.',
				'sku'   => '',
			],
			3 => [
				'title' => 'Foo 4.',
				'sku'   => '0',
			],
			4 => [
				'title' => 'Bar 5.',
				'sku'   => '1',
			],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );
	}
}
