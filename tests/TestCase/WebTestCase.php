<?php

namespace SyncEngine\Tests\TestCase;

use SyncEngine\Service\System;

abstract class WebTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$system = static::getContainer()->get( System::class );
		$system->getEnv()->setEnvFile( 'test' );
	}
}
