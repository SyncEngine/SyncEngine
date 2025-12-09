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
}
