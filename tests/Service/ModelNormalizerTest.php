<?php

namespace SyncEngine\Tests\Service;

use SyncEngine\Service\ModelNormalizer;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ModelNormalizerTest extends BaseTestCase
{
	public function testCleanupConfig(): void
	{
		/** @var ModelNormalizer $normalizer */
		$normalizer = static::getContainer()->get( ModelNormalizer::class );

		$config = [
			'foo' => 'bar',
			'bar' => 'baz',
			'nope' => false,
		];

		$fields = [
			'foo' => [
				'type' => 'text',
				'name' => 'foo',
			],
			'bar' => [
				'type' => 'text',
				'name' => 'bar',
			],
			'nope' => [
				'type' => 'text',
				'name' => 'nope',
			]
		];

		$cleaned = $normalizer->cleanupConfig( $config, $fields );

		$this->assertEquals( $config, $cleaned );

		$fields['nope']['conditions'] = [ 'foo' => true ];
		$expected = $config;

		$cleaned = $normalizer->cleanupConfig( $config, $fields );

		$this->assertEquals( $expected, $cleaned );

		$config['foo'] = '';
		$cleaned = $normalizer->cleanupConfig( $config, $fields );
		$expected = $config;
		unset( $expected['nope'] );

		$this->assertEquals( $expected, $cleaned );

		/* NESTED */

		$config = [
			'foo' => 'bar',
			'bar' => [
				'one' => 'one',
				'two' => 'two',
				'three' => 'three',
			],
			'nope' => false,
		];

		$fields = [
			'foo' => [
				'type' => 'text',
				'name' => 'foo',
			],
			'bar' => [
				'type' => 'text',
				'name' => 'bar',
				'nested' => [
					'one' => [
						'type' => 'text',
					],
					'two' => [
						'type' => 'text',
						'conditions' => [ 'one' => 1 ]
					],
					'three' => [
						'type' => 'text',
						'conditions' => [ 'two' => 2 ]
					],
				]
			],
			'nope' => [
				'type' => 'text',
				'name' => 'nope',
			]
		];

		$cleaned = $normalizer->cleanupConfig( $config, $fields );
		$expected = $config;
		$expected['bar'] = [ 'one' => 'one' ];
		$this->assertEquals( $expected, $cleaned );

		$config['bar'] = [ 'one' => 1, 'two' => 2, 'three' => 3 ];
		$expected = $config;
		$cleaned = $normalizer->cleanupConfig( $config, $fields );
		$this->assertEquals( $expected, $cleaned );

		$config['bar'] = [ 'one' => 1, 'two' => '2', 'three' => 3 ];
		$expected = $config;
		unset( $expected['bar']['three'] );
		$cleaned = $normalizer->cleanupConfig( $config, $fields );
		$this->assertEquals( $expected, $cleaned );

		/* Fully conditional nested group. */
		$fields['bar']['conditions'] = [ 'foo' => 'bar' ];
		$config['bar'] = [ 'one' => 1, 'two' => 2, 'three' => 3 ];
		$expected = $config;
		$cleaned = $normalizer->cleanupConfig( $config, $fields );
		$this->assertEquals( $expected, $cleaned );

		$fields['bar']['conditions'] = [ 'foo' => 'nope' ];
		unset( $expected['bar'] );
		$cleaned = $normalizer->cleanupConfig( $config, $fields );
		$this->assertEquals( $expected, $cleaned );


		/* SUB-FIELDS */

		$config = [
			'foo' => 'bar',
			'bar' => '',
			'one' => 'one',
			'two' => 'two',
			'three' => 'three',
			'nope' => false,
		];

		$fields = [
			'foo' => [
				'type' => 'text',
				'name' => 'foo',
			],
			'bar' => [
				'type' => 'text',
				'name' => 'bar',
				'fields' => [
					'one' => [
						'type' => 'text',
					],
					'two' => [
						'type' => 'text',
						'conditions' => [ 'one' => 1 ]
					],
					'three' => [
						'type' => 'text',
						'conditions' => [ 'two' => 2 ]
					],
				]
			],
			'nope' => [
				'type' => 'text',
				'name' => 'nope',
			]
		];

		$cleaned = $normalizer->cleanupConfig( $config, $fields );
		$expected = $config;
		unset( $expected['two'] );
		unset( $expected['three'] );
		$this->assertEquals( $expected, $cleaned );

		$config['one'] = 1;
		$config['two'] = 2;
		$config['three'] = 3;
		$expected = $config;
		$cleaned = $normalizer->cleanupConfig( $config, $fields );
		$this->assertEquals( $expected, $cleaned );

		$config['two'] = '2';
		$expected = $config;
		unset( $expected['three'] );
		$cleaned = $normalizer->cleanupConfig( $config, $fields );
		$this->assertEquals( $expected, $cleaned );
	}
}
