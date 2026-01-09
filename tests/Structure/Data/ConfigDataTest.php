<?php

namespace SyncEngine\Tests\Structure\Data;

use SyncEngine\Service\ModelNormalizer;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Task\Set;
use SyncEngine\Task\Trigger;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ConfigDataTest extends BaseTestCase
{
	public function testSanitize(): void
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
			],
		];

		$sanitized = ConfigData::create( $config )->sanitize( $fields );

		$this->assertEquals( $config, $sanitized );

		$fields['nope']['conditions'] = [ 'foo' => true ];
		$expected                     = $config;

		$sanitized = ConfigData::create( $config )->sanitize( $fields );

		$this->assertEquals( $expected, $sanitized );

		$config['foo'] = '';
		$sanitized     = ConfigData::create( $config )->sanitize( $fields );
		$expected      = $config;
		unset( $expected['nope'] );

		$this->assertEquals( $expected, $sanitized );
	}

	public function testSanitizeNested(): void
	{
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
						'conditions' => [ 'one' => 1 ],
					],
					'three' => [
						'type'       => 'text',
						'conditions' => [ 'two' => 2 ],
					],
				],
			],
			'nope' => [
				'type' => 'text',
				'name' => 'nope',
			],
		];

		$sanitized       = ConfigData::create( $config )->sanitize( $fields );
		$expected        = $config;
		$expected['bar'] = [ 'one' => 'one' ];
		$this->assertEquals( $expected, $sanitized );

		$config['bar'] = [ 'one' => 1, 'two' => 2, 'three' => 3 ];
		$expected      = $config;
		$sanitized     = ConfigData::create( $config )->sanitize( $fields );
		$this->assertEquals( $expected, $sanitized );

		$config['bar'] = [ 'one' => 1, 'two' => '2', 'three' => 3 ];
		$expected      = $config;
		unset( $expected['bar']['three'] );
		$sanitized = ConfigData::create( $config )->sanitize( $fields );
		$this->assertEquals( $expected, $sanitized );

		/* Fully conditional nested group. */
		$fields['bar']['conditions'] = [ 'foo' => 'bar' ];
		$config['bar']               = [ 'one' => 1, 'two' => 2, 'three' => 3 ];
		$expected                    = $config;
		$sanitized                   = ConfigData::create( $config )->sanitize( $fields );
		$this->assertEquals( $expected, $sanitized );

		$fields['bar']['conditions'] = [ 'foo' => 'nope' ];
		unset( $expected['bar'] );
		$sanitized = ConfigData::create( $config )->sanitize( $fields );
		$this->assertEquals( $expected, $sanitized );
	}

	public function testSanitizeDuplicateNames(): void
	{
		$config = [
			'foo'  => 'bar',
			'bar'  => 'test',
			'nope' => false,
		];

		$fields = [
			'foo'     => [
				'type' => 'text',
				'name' => 'foo',
			],
			'bar_one' => [
				'type'       => 'text',
				'name'       => 'bar',
				'conditions' => [ 'foo' => 'bar' ],
			],
			'bar_two' => [
				'type'       => 'text',
				'name'       => 'bar',
				'conditions' => [ 'foo' => 'test' ],
			],
			'nope'    => [
				'type' => 'text',
				'name' => 'nope',
			],
		];

		$sanitized       = ConfigData::create( $config )->sanitize( $fields );
		$expected        = $config;
		$this->assertEquals( $expected, $sanitized );

		$config = [
			'foo'  => 'test', // Also valid through "bar_two".
			'bar'  => 'test',
			'nope' => false,
		];

		$sanitized       = ConfigData::create( $config )->sanitize( $fields );
		$expected        = $config;
		$this->assertEquals( $expected, $sanitized );

		$config = [
			'foo'  => 'nope', // Not valid by either "bar_one" or "bar_two".
			'bar'  => 'test',
			'nope' => false,
		];

		$sanitized       = ConfigData::create( $config )->sanitize( $fields );
		$expected        = $config;
		unset( $expected['bar'] );
		$this->assertEquals( $expected, $sanitized );
	}

	public function testSanitizeConfigRecursive(): void
	{
		/** @var ModelNormalizer $normalizer */
		$normalizer = static::getContainer()->get( ModelNormalizer::class );

		/* SUB-FIELDS */

		$config = [
			'foo'   => 'bar',
			'bar'   => '',
			'one'   => 'one',
			'two'   => 'two',
			'three' => 'three',
			'nope'  => false,
		];

		$fields = [
			'foo'  => [
				'type' => 'text',
			],
			'bar'  => [
				'type'   => 'text',
				'fields' => [
					'one'   => [
						'type' => 'text',
					],
					'two'   => [
						'type'       => 'text',
						'conditions' => [ 'one' => 1 ],
					],
					'three' => [
						'type'       => 'text',
						'conditions' => [ 'two' => 2 ],
					],
				],
			],
			'nope' => [
				'type' => 'text',
			],
		];

		$sanitized = ConfigData::create( $config )->sanitize( $fields );
		$expected  = $config;
		unset( $expected['two'] );
		unset( $expected['three'] );
		$this->assertEquals( $expected, $sanitized );

		$config['one']   = 1;
		$config['two']   = 2;
		$config['three'] = 3;
		$expected        = $config;
		$sanitized       = ConfigData::create( $config )->sanitize( $fields );
		$this->assertEquals( $expected, $sanitized );

		$config['two'] = '2';
		$expected      = $config;
		unset( $expected['three'] );
		$sanitized = ConfigData::create( $config )->sanitize( $fields );
		$this->assertEquals( $expected, $sanitized );

		/* Lets get serious now */

		$config = [
			'foo'   => 'bar',
			'tasks' => [
				[
					'_class' => Trigger::_getClassLocator(),
					'action' => 'tasks',
					'flow'   => 1, // Should be removed.
					'tasks'  => [
						[
							'_class'             => Set::_getClassLocator(),
							'set'                => 'schema',
							'params'             => [
								// Should be removed.
								'to'        => 'be',
								'sanitized' => true,
							],
							'non_existing_param' => 'test',
							// Should be kept because there is not conditional available for removal.
						],
					],
				],
			],
			'nope'  => false,
		];

		$fields = [
			'foo'   => [
				'type' => 'text',
			],
			'tasks' => [
				'type' => 'tasks',
			],
			'nope'  => [
				'type' => 'text',
			],
		];

		$expected = $config;
		unset( $expected['tasks'][0]['flow'] );
		unset( $expected['tasks'][0]['tasks'][0]['params'] );

		$sanitized = ConfigData::create( $config )->sanitize( $fields );
		$this->assertEquals( $expected, $sanitized );
		$this->assertTrue( $sanitized === $expected );

		/* Ensure default value is set if not provided */

		$config = [
			'foo'   => 'bar',
			'tasks' => [
				[
					'_class' => Trigger::_getClassLocator(),
					'action' => 'tasks',
					'flow'   => 1, // Should be removed.
					'tasks'  => [
						[
							'_class'             => Set::_getClassLocator(),
							// 'set'    => 'params', << Should be added automatically.
							'params'             => [
								// Should be removed.
								'to'        => 'be',
								'sanitized' => true,
							],
							'non_existing_param' => 'test',
							// Should be kept because there is not conditional available for removal.
						],
					],
				],
			],
			'nope'  => false,
		];

		$fields = [
			'foo'   => [
				'type' => 'text',
			],
			'tasks' => [
				'type' => 'tasks',
			],
			'nope'  => [
				'type' => 'text',
			],
		];

		$expected = $config;
		unset( $expected['tasks'][0]['flow'] );
		$expected['tasks'][0]['tasks'][0]['set'] = 'params';

		$sanitized = ConfigData::create( $config )->sanitize( $fields );
		$this->assertEquals( $expected, $sanitized );
		$this->assertTrue( $sanitized === $expected );
	}
}
