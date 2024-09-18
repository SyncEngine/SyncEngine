<?php

namespace SyncEngine\Tests\Model;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ConnectionModelTest extends BaseTestCase
{
	public function clearInternalConfig( $config )
	{
		unset( $config['_class'] );
		unset( $config['_connect'] );
		unset( $config['authorization'] );
		unset( $config['authorize'] );
		unset( $config['connection'] );
		unset( $config['variables'] );

		return $config;
	}

	public function getModel( $config )
	{
		$model = new ConnectionModel();
		$model->setConfig( $config );

		return $model;
	}

	public function testConnectionAuthorization(): void
	{
		$model = $this->getModel(
			[
				'webservice' => [
					'_class'    => 'Http',
					'host'      => 'https://localhost',
					'_connect'  => [
						'request'  => [
							'query'  => [
								'connect' => 'Test Merge',
							],
							'method' => 'HEAD',
						],
						'endpoint' => 'Test Connect Override',
						'response' => [],
					],
					'authorize' => [
						'host'     => 'https:127.0.0.1',
						'request'  => [
							'headers' => [
								'Authorization' => 'Test Authorization',
							],
							'format'  => 'formdata',
						],
						'endpoint' => 'Test Authorize Override',
						'response' => [
							'format' => 'json',
						],
					],
				],
			]
		);

		//* Test connect config.

		$expected = [
			'host'     => 'https:127.0.0.1',
			'request'  => [
				'headers' => [
					'Authorization' => 'Test Authorization',// From Authorize
				],
				'query'   => [
					'connect' => 'Test Merge',
				],
				'method'  => 'HEAD',
				'format'  => 'formdata',// From Authorize
			],
			'endpoint' => 'Test Connect Override',
			'response' => [
				'format' => 'json',// From Authorize
			],
		];

		$actual = $model->handleAuthorization( $model->getConfig('webservice._connect' ), null );
		$actual = $this->clearInternalConfig( $actual );

		$this->assertEquals( $expected, $actual );

		//* Test task config.

		$config = [
			'endpoint' => 'foobar',
			'request'  => [
				'headers' => [
					'Foo' => 'Bar',
				],
			],
		];

		$expected = [
			'host'     => 'https:127.0.0.1',
			'request'  => [
				'headers' => [
					'Authorization' => 'Test Authorization',
					'Foo'           => 'Bar',
				],
				'format'  => 'formdata',
			],
			'endpoint' => 'foobar',
			'response' => [
				'format' => 'json',
			],
		];

		$actual = $model->handleAuthorization( $config, null );
		$actual = $this->clearInternalConfig( $actual );

		$this->assertEquals( $expected, $actual );

		//* Test task overrides config.

		$config = [
			'host'     => 'NOPE!', // Should not be possible.
			'endpoint' => 'foobar',
			'request'  => [
				'headers' => [
					'Authorization' => 'Override Authorization',
					'Foo'           => 'Bar',
				],
			],
		];

		$expected = [
			'host'     => 'https:127.0.0.1',
			'request'  => [
				'headers' => [
					'Authorization' => 'Override Authorization',
					'Foo'           => 'Bar',
				],
				'format'  => 'formdata',
			],
			'endpoint' => 'foobar',
			'response' => [
				'format' => 'json',
			],
		];

		$actual = $model->handleAuthorization( $config, null );
		$actual = $this->clearInternalConfig( $actual );

		$this->assertEquals( $expected, $actual );
	}

	public function testConnectionAuthorizationTags(): void
	{
		$model = $this->getModel(
			[
				'webservice' => [
					'_class'    => 'Http',
					'host'      => 'https://localhost',
					'variables' => [
						'foo' => 'bar',
					],
					'authorize' => [
						'host'     => 'https://{{ variables.foo }}.com',
					],
				],
			]
		);

		//* Test variables

		$config = [];

		$expected = [
			'host'     => 'https://bar.com',
		];

		$actual = $model->handleAuthorization( $config, null );
		$actual = $this->clearInternalConfig( $actual );

		$this->assertEquals( $expected, $actual );

		//* Make sure tasks cannot use auth tags!

		$config = [
			'endpoint' => '{{ variables.foo }}',
		];

		$expected = [
			'host'     => 'https://bar.com',
			'endpoint' => '{{ variables.foo }}', // Not supported!
		];

		$actual = $model->handleAuthorization( $config, null );
		$actual = $this->clearInternalConfig( $actual );

		$this->assertEquals( $expected, $actual );

	}
}
