<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class FilterTest extends TaskTestCase
{
	protected string $_task = 'Filter';

	public function testFilter(): void
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

		$config = [
			'key'        => '',
			'method'     => 'valid',
			'conditions' => [
				[
					'key' => 'sku',
					'operator' => 'in',
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

		$config = [
			'key'        => '',
			'method'     => 'invalid',
			'conditions' => [
				[
					'key' => 'sku',
					'operator' => 'in',
					'compare' => '1',
				]
			],
		];

		$returnData = $this->execute( $config, $context, $data );

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

		$this->assertEquals( $expected, $returnData );
	}
}
