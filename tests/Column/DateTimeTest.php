<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Column\DateTime;
use SyncEngine\Service\Format\DateTimeFormatter;
use SyncEngine\Tests\TestCase\ColumnTestCase;

class DateTimeTest extends ColumnTestCase
{
	/**
	 * @see DateTime
	 */
	protected string $_column = 'DateTime';

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

		/**
		 * Change format from unknown but convertable format (US default)
		 */
		$value = '02/14/2007 08:25:00 am';

		$targetSchema = [
			'format' => 'Y-m-d H:i:s',
		];

		// The source format is not convertable to falls back to today.
		$expected = '2007-02-14 08:25:00';

		$formatted = ( new DateTimeFormatter( $targetSchema ) )->convert( $value );

		$this->assertEquals( $expected, $formatted );

		/**
		 * Change format from unknown that isn't convertable.
		 * It looks like US default but it's d/m/Y instead of m/d/Y
		 */
		$value = '14/02/2007 08:25:00 am';

		$targetSchema = [
			'format' => 'Y-m-d', // Omit time to be sure the tests won't fail.
		];

		// The source format is not convertable to falls back to today.
		$expected = date( 'Y-m-d' );

		$formatted = ( new DateTimeFormatter( $targetSchema ) )->convert( $value );

		$this->assertEquals( $expected, $formatted );

		/**
		 * Timezone conversion.
		 * @todo In case of failure, check summer/winder time.
		 */

		$value = "2007-02-14 20:25:25";

		$sourceSchema = [
			'format' => 'Y-m-d H:i:s',
			'timezone' => 'America/Chicago',
		];

		$targetSchema = [
			'format' => 'Y-m-d H:i:s',
			'timezone' => 'UTC',
		];

		$expected = "2007-02-15 02:25:25";

		$formatted = ( new DateTimeFormatter( $targetSchema ) )->convert( $value, new DateTimeFormatter( $sourceSchema ) );

		$this->assertEquals( $expected, $formatted );

		/**
		 * Timezone shorthand conversion.
		 * @todo In case of failure, check summer/winder time.
		 */

		$value = "2007-02-14 20:25:25";

		$sourceSchema = [
			'format' => 'Y-m-d H:i:s',
			'timezone' => 'CDT',
		];

		$targetSchema = [
			'format' => 'Y-m-d H:i:s',
			'timezone' => 'CET',
		];

		$expected = "2007-02-15 02:25:25";

		$formatted = ( new DateTimeFormatter( $targetSchema ) )->convert( $value, new DateTimeFormatter( $sourceSchema ) );

		$this->assertEquals( $expected, $formatted );

	}

	public function testEmptyValue()
	{
		$column = $this->getColumn();

		// Basic, valid value, not changed.
		$value = "2007-02-14 20:25:25";

		$config = [
			'format' => 'Y-m-d H:i:s',
			'empty'  => '{*null*}',
		];

		$formatted = $column->format( $value, $config );

		$this->assertEquals( $value, $formatted );

		/**
		 * Empty value tests.
		 */
		$value = '';

		// Empty value as null
		$config['empty'] = '{*null*}';
		$formatted = $column->format( $value, $config );
		$this->assertNull( $formatted );

		// Empty value as false
		$config['empty'] = '{*false*}';
		$formatted = $column->format( $value, $config );
		$this->assertFalse( $formatted );

		// Empty value as false
		$config['empty'] = '{*empty*}';
		$formatted = $column->format( $value, $config );
		$this->assertEquals( '', $formatted );

		// Empty value as false
		$config['empty'] = '{*now*}';
		$config['format'] = 'Y-m-d';
		$now = date( $config['format'] );
		$formatted = $column->format( $value, $config );
		$this->assertEquals( $formatted, $now );

		// Empty value as null (string)
		$config['empty'] = 'null';
		$formatted = $column->format( $value, $config );
		$this->assertEquals( 'null', $formatted );

		// Empty value as false (string)
		$config['empty'] = 'false';
		$formatted = $column->format( $value, $config );
		$this->assertEquals( 'false', $formatted );

		// Empty value as custom
		$config['empty'] = '0000-00-00';
		$formatted = $column->format( $value, $config );
		$this->assertEquals( '0000-00-00', $formatted );
	}
}
