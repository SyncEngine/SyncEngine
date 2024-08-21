<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Service\Format\EmailFormatter;
use SyncEngine\Tests\TestCase\BaseTestCase;

class EmailTest extends BaseTestCase
{
	public function testFormatSchema(): void
	{
		//* Valid email
		$value = 'user@domain.com';
		$expected = 'user@domain.com';

		$targetSchema = [];

		$formatted = ( new EmailFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( $expected, $formatted );

		//* Remove spaces
		$value = ' user @ domain.com';
		$expected = 'user@domain.com';

		$targetSchema = [];

		$formatted = ( new EmailFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( $expected, $formatted );

		//* Support tags.
		$value = 'user+tag@domain.com';
		$expected = 'user+tag@domain.com';

		$formatted = ( new EmailFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( $expected, $formatted );
	}
}
