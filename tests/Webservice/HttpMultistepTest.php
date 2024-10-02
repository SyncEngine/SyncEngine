<?php

namespace SyncEngine\Tests\Webservice;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Tests\TestCase\BaseTestCase;
use SyncEngine\Webservice\HttpMultistep;

class HttpMultistepTest extends BaseTestCase
{
	/**
	 * @return HttpMultistep
	 */
	public function getWebservice(): WebserviceModel
	{
		return WebserviceModel::get( 'HttpMultistep' );
	}

	public function testExpiration(): void
	{
		$webservice = $this->getWebservice();

		$auth = [
			'refs'    => [
				'step1' => [
					0 => 'cookie',
					1 => 'tmp',
				],
				'step2' => [
					0 => 'code',
				],
				'step3' => [
					0 => 'token',
				],
				'step4' => [
					0 => 'cookie',
				],
				'step5' => [
					0 => 'tmp',
				],
				'step6' => [
					0 => 'exp',
				],
			],
			'tags'    => [
				'cookie' => '',
				'tmp'    => '',
				'exp'    => '',
				'code'   => '',
				'token'  => '',
			],
			'expires' => [
				'cookie' => false,
				'tmp'    => true,
				'exp'    => strtotime( '- 10 minutes' ),
				'code'   => strtotime( '+ 10 minutes' ),
				'token'  => '',
			],
		];

		$connection = ConnectionModel::create();
		$connection->setData( $auth, 'auth' );

		/**
		 * Without values, all should be expired.
		 */

		$config = [ '_ref' => 'step1', ];
		$this->assertTrue( $webservice->isAuthExpired( $config, $connection ) );

		$config = [ '_ref' => 'step2', ];
		$this->assertTrue( $webservice->isAuthExpired( $config, $connection ) );

		$config = [ '_ref' => 'step3', ];
		$this->assertTrue( $webservice->isAuthExpired( $config, $connection ) );

		$config = [ '_ref' => 'step4', ];
		$this->assertTrue( $webservice->isAuthExpired( $config, $connection ) );

		$config = [ '_ref' => 'step5', ];
		$this->assertTrue( $webservice->isAuthExpired( $config, $connection ) );

		$config = [ '_ref' => 'step6', ];
		$this->assertTrue( $webservice->isAuthExpired( $config, $connection ) );

		/**
		 * With values.
		 */

		$tags = [
			'cookie' => 'cookie', // false
			'tmp'    => 'tmp', // true
			'exp'    => 'exp', // expired tag
			'code'   => 'code', // valid tag
			'token'  => 'token', // empty tag
		];

		$connection->setData( $tags, 'auth.tags' );

		$config = [ '_ref' => 'step1', ];
		$this->assertTrue( $webservice->isAuthExpired( $config, $connection ) );

		$config = [ '_ref' => 'step2', ];
		$this->assertFalse( $webservice->isAuthExpired( $config, $connection ) );

		$config = [ '_ref' => 'step3', ];
		$this->asserttrue( $webservice->isAuthExpired( $config, $connection ) );

		$config = [ '_ref' => 'step4', ];
		$this->assertFalse( $webservice->isAuthExpired( $config, $connection ) );

		$config = [ '_ref' => 'step5', ];
		$this->assertTrue( $webservice->isAuthExpired( $config, $connection ) );

		$config = [ '_ref' => 'step6', ];
		$this->assertTrue( $webservice->isAuthExpired( $config, $connection ) );
	}
}
