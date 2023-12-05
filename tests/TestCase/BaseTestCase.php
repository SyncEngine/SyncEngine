<?php

namespace App\Tests\TestCase;

use App\Controller\DefaultController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class BaseTestCase extends KernelTestCase
{
	public function setUp(): void
	{
		self::bootKernel();
		parent::setUp();

		// Initialize globals.
		if ( ! DefaultController::getClassFinder() ) {
			static::getContainer()->get( DefaultController::class );
		}
	}
}
