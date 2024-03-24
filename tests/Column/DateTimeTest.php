<?php

namespace Column;

use SyncEngine\Service\Format\DateTimeFormatter;
use SyncEngine\Tests\TestCase\BaseTestCase;

class DateTimeTest extends BaseTestCase
{
	public function testFormatSchema(): void
	{
		// Basic, not changed.
		$value = "2007-02-14 20:25:25";

		$targetSchema = [
			'format' => 'Y-m-d H:i:s',
		];

		$formatted = ( new DateTimeFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( $value, $formatted );

		/**
		 * Change format.
		 */

		$targetSchema = [
			'format' => 'm.d.y g:i',
		];

		$expected = '02.14.07 8:25';

		$formatted = ( new DateTimeFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( $expected, $formatted );

		/**
		 * Change format to timestamp.
		 */

		$targetSchema = [
			'format' => 'U',
		];

		$expected = '1171484725';

		$formatted = ( new DateTimeFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( $expected, $formatted );
	}
}
