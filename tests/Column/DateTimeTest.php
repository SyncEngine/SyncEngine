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

	public function testConvertSchema(): void
	{
		/**
		 * Change format from source format.
		 */
		$value = '02.14.07 8:25';

		$sourceSchema = [
			'format' => 'm.d.y g:i',
		];

		$targetSchema = [
			'format' => 'd/m/Y h:i:s a',
		];

		$expected = '14/02/2007 08:25:00 am';

		$formatted = ( new DateTimeFormatter( $targetSchema ) )->convert( $value, new DateTimeFormatter( $sourceSchema ) );

		$this->assertEquals( $expected, $formatted );

		/**
		 * Change format from source format timestamp.
		 */
		$value = '1171484725';

		$sourceSchema = [
			'format' => 'U',
		];

		$targetSchema = [
			'format' => 'D M j G:i:s T Y',
		];

		// @todo Translations?
		$expected = 'Wed Feb 14 20:25:25 GMT+0000 2007';

		$formatted = ( new DateTimeFormatter( $targetSchema ) )->convert( $value, new DateTimeFormatter( $sourceSchema ) );

		$this->assertEquals( $expected, $formatted );
	}
}
