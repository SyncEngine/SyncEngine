<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Service\Format\EmailFormatter;
use SyncEngine\Tests\TestCase\ColumnTestCase;

class EmailTest extends ColumnTestCase
{
	protected string $_column = 'Email';

	public function testFormatSchema(): void
	{
		//* Valid email
		$value     = 'user@domain.com';
		$expected  = 'user@domain.com';
		$formatted = ( new EmailFormatter( [] ) )->format( $value );
		$this->assertEquals( $expected, $formatted );

		//* Remove spaces
		$value     = ' user @ domain.com';
		$expected  = 'user@domain.com';
		$formatted = ( new EmailFormatter( [] ) )->format( $value );
		$this->assertEquals( $expected, $formatted );

		//* Support tags.
		$value     = 'user+tag@domain.com';
		$expected  = 'user+tag@domain.com';
		$formatted = ( new EmailFormatter( [] ) )->format( $value );
		$this->assertEquals( $expected, $formatted );

		//* Support dots.
		$value     = 'user.tag@domain.com';
		$expected  = 'user.tag@domain.com';
		$formatted = ( new EmailFormatter( [] ) )->format( $value );
		$this->assertEquals( $expected, $formatted );
	}
}
