<?php

namespace SyncEngine\Tests\Structure\Data;

use SyncEngine\Service\ModelNormalizer;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Task\Set;
use SyncEngine\Task\Trigger;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ConfigDataTest extends BaseTestCase
{
	public function testCleanup(): void
	{
		$config = [
			'foo'  => 'bar',
			'bar'  => 'baz',
			'nope' => false,
		];

		$fields = [
			'foo'  => [
				'type' => 'text',
				'name' => 'foo',
			],
			'bar'  => [
				'type' => 'text',
				'name' => 'bar',
			],
			'nope' => [
				'type' => 'text',
				'name' => 'nope',
			]
		];

		$cleaned = ConfigData::create( $config )->cleanup( $config, $fields );

		$this->assertEquals( $config, $cleaned );

		$fields['nope']['conditions'] = [ 'foo' => true ];
		$expected                     = $config;

		$cleaned = ConfigData::create( $config )->cleanup( $config, $fields );

		$this->assertEquals( $expected, $cleaned );

		$config['foo'] = '';
		$cleaned       = ConfigData::create( $config )->cleanup( $config, $fields );
		$expected      = $config;
		unset( $expected['nope'] );

		$this->assertEquals( $expected, $cleaned );

		/* NESTED */

		$config = [
			'foo'  => 'bar',
			'bar'  => [
				'one'   => 'one',
				'two'   => 'two',
				'three' => 'three',
			],
			'nope' => false,
		];

		$fields = [
			'foo'  => [
				'type' => 'text',
				'name' => 'foo',
			],
			'bar'  => [
				'type'   => 'text',
				'name'   => 'bar',
				'nested' => [
					'one'   => [
						'type' => 'text',
					],
					'two'   => [
						'type'       => 'text',
						'conditions' => [ 'one' => 1 ]
					],
					'three' => [
						'type'       => 'text',
						'conditions' => [ 'two' => 2 ]
					],
				]
			],
			'nope' => [
				'type' => 'text',
				'name' => 'nope',
			]
		];

		$cleaned         = ConfigData::create( $config )->cleanup( $config, $fields );
		$expected        = $config;
		$expected['bar'] = [ 'one' => 'one' ];
		$this->assertEquals( $expected, $cleaned );

		$config['bar'] = [ 'one' => 1, 'two' => 2, 'three' => 3 ];
		$expected      = $config;
		$cleaned       = ConfigData::create( $config )->cleanup( $config, $fields );
		$this->assertEquals( $expected, $cleaned );

		$config['bar'] = [ 'one' => 1, 'two' => '2', 'three' => 3 ];
		$expected      = $config;
		unset( $expected['bar']['three'] );
		$cleaned = ConfigData::create( $config )->cleanup( $config, $fields );
		$this->assertEquals( $expected, $cleaned );

		/* Fully conditional nested group. */
		$fields['bar']['conditions'] = [ 'foo' => 'bar' ];
		$config['bar']               = [ 'one' => 1, 'two' => 2, 'three' => 3 ];
		$expected                    = $config;
		$cleaned                     = ConfigData::create( $config )->cleanup( $config, $fields );
		$this->assertEquals( $expected, $cleaned );

		$fields['bar']['conditions'] = [ 'foo' => 'nope' ];
		unset( $expected['bar'] );
		$cleaned = ConfigData::create( $config )->cleanup( $config, $fields );
		$this->assertEquals( $expected, $cleaned );
	}

	public function testCleanupConfigRecursive(): void
	{
		/** @var ModelNormalizer $normalizer */
		$normalizer = static::getContainer()->get( ModelNormalizer::class );

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
			],
			'bar' => [
				'type' => 'text',
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
			]
		];

		$cleaned = ConfigData::create( $config )->cleanup( $config, $fields );
		$expected = $config;
		unset( $expected['two'] );
		unset( $expected['three'] );
		$this->assertEquals( $expected, $cleaned );

		$config['one'] = 1;
		$config['two'] = 2;
		$config['three'] = 3;
		$expected = $config;
		$cleaned = ConfigData::create( $config )->cleanup( $config, $fields );
		$this->assertEquals( $expected, $cleaned );

		$config['two'] = '2';
		$expected = $config;
		unset( $expected['three'] );
		$cleaned = ConfigData::create( $config )->cleanup( $config, $fields );
		$this->assertEquals( $expected, $cleaned );

		/* Lets get serious now */

		$config = [
			'foo' => 'bar',
			'tasks' => [
				[
					'_class' => Trigger::_getClassLocator(),
					'action' => 'tasks',
					'flow' => 1, // Should be removed.
					'tasks' => [
						[
							'_class' => Set::_getClassLocator(),
							'set' => 'schema',
							'params' => [ // Should be removed.
							              'to' => 'be',
							              'cleaned' => true,
							]
						]
					]
				]
			],
			'nope' => false,
		];

		$fields = [
			'foo' => [
				'type' => 'text',
			],
			'tasks' => [
				'type' => 'tasks',
			],
			'nope' => [
				'type' => 'text',
			]
		];

		$expected = $config;
		unset( $expected['tasks'][0]['flow'] );
		unset( $expected['tasks'][0]['tasks'][0]['params'] );

		$cleaned = ConfigData::create( $config )->cleanup( $config, $fields );
		$this->assertEquals( $expected, $cleaned );
		$this->assertTrue( $cleaned === $expected );
	}
}
