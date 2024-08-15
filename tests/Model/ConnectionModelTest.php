<?php

namespace SyncEngine\Tests\Model;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ConnectionModelTest extends BaseTestCase
{
	public $configHttp = [
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
	];

	public function clearInternalConfig( $config )
	{
		unset( $config['_class'] );
		unset( $config['_connect'] );
		unset( $config['authorization'] );
		unset( $config['authorize'] );
		unset( $config['connection'] );

		return $config;
	}

	public function testConnectionAuthorization(): void
	{
		$model = new ConnectionModel();

		$model->setConfig( [ 'webservice' => $this->configHttp, ] );

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

		$actual = $model->handleAuthorization( $this->configHttp['_connect'], null );
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
	}
}
