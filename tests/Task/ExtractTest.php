<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class ExtractTest extends TaskTestCase
{
	protected string $_task = 'Extract';

	public function testExtract(): void
	{
		$context = $this->getContext();

		$data = [
			0            => [
				'title'  => 'Test 1.',
				'fields' => [
					'sku' => 'ABCD.001/23456',
				],
			],
			1            => [
				'title'  => 'Test 2.',
				'fields' => [
					'sku' => '23456.ABCD.001',
				],
			],
			'random key' => [
				'title'  => 'Test 3.',
				'fields' => [
					'sku' => '',
				],
			],
			'foo bar'    => [
				'title'  => 'Foo 4.',
				'fields' => [
					'sku' => '0',
				],
			],
			66           => [
				'title'  => 'Bar 5.',
				'fields' => [
					'sku' => '1',
				],
			],
		];

		$config = [
			'column_key' => 'fields',
		];

		$expected = [
			0            => [
				'sku' => 'ABCD.001/23456',
			],
			1            => [
				'sku' => '23456.ABCD.001',
			],
			'random key' => [
				'sku' => '',
			],
			'foo bar'    => [
				'sku' => '0',
			],
			66           => [
				'sku' => '1',
			],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );

		//* Nested tests

		$data = [
			0            => [
				'title'  => 'Test 1.',
				'fields' => [
					'foo' => [
						'bar' => [
							'sku' => 'ABCD.001/23456',
						],
					],
				],
			],
			1            => [
				'title'  => 'Test 2.',
				'fields' => [
					'foo' => [
						'bar' => [
							'sku' => '23456.ABCD.001',
						],
					],
				],
			],
			'random key' => [
				'title'  => 'Test 3.',
				'fields' => [
					'foo' => [
						'bar' => [
							'sku' => '',
						],
					],
				],
			],
			'foo bar'    => [
				'title'  => 'Foo 4.',
				'fields' => [
					'foo' => [
						'bar' => [
							'sku' => '0',
						],
					],
				],
			],
			66           => [
				'title'  => 'Bar 5.',
				'fields' => [
					'foo' => [
						'bar' => [
							'sku' => '1',
						],
					],
				],
			],
		];

		$config = [
			'column_key' => 'fields.foo.bar',
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, $returnData );
	}
}
