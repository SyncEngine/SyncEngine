<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Service\Format\DurationFormatter;
use SyncEngine\Tests\TestCase\BaseTestCase;

class DurationTest extends BaseTestCase
{
	public function testFormatSchema(): void
	{
		// Simple test.

		$value = '10 hours 5 minutes';

		$expected = strtotime( '10 hours 5 minutes' );

		$formatted = ( new DurationFormatter( [] ) )->toTimestamp( $value );

		$this->assertEquals( $expected, $formatted );

		// Timestamp check.

		$formatted = ( new DurationFormatter( [] ) )->toTimestamp( $expected );

		$this->assertEquals( $expected, $formatted );

		// Custom time notation.

		$value = '10:05';

		$expected = strtotime( '10 hours 5 minutes' );

		$formatted = ( new DurationFormatter( [] ) )->toTimestamp( $value );

		$this->assertEquals( $expected, $formatted );

		// Formatted

		$targetSchema = [
			'format' => '%h-%i-%s',
		];

		$expected = '10-5-0';

		$formatted = ( new DurationFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( $expected, $formatted );

		// Specific date.

		$targetSchema = [
			'now' => strtotime( '-2 days' ),
			'format' => '%d %h:%i:%s',
		];

		$expected = '2 10:5:0';

		$formatted = ( new DurationFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( $expected, $formatted );

		// Relative date.

		$targetSchema = [
			'now' => strtotime( 'yesterday' ),
			'format' => '%d %h:%i:%s',
		];

		$yesterday = new \DateTimeImmutable( 'yesterday' );
		$expected = $yesterday->diff( new \DateTimeImmutable( '10 hours 5 minutes' ) )->format( $targetSchema['format'] );

		$formatted = ( new DurationFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( $expected, $formatted );

		// Specific duration format.

		$targetSchema = [
			'now'    => strtotime( 'now' ),
			'format' => 'days',
		];

		$expected = '7';

		$formatted = ( new DurationFormatter( $targetSchema ) )->format( '+1 week' );

		$this->assertEquals( $expected, $formatted );

		$formatted = ( new DurationFormatter( $targetSchema ) )->format( strtotime( '+1 week' ) );

		$this->assertEquals( $expected, $formatted );

		$targetSchema['format'] = 'seconds';

		$expected = '1209600'; // 604800 * 2.

		$formatted = ( new DurationFormatter( $targetSchema ) )->format( '+2 weeks' );

		$this->assertEquals( $expected, $formatted );

		// Convert to time unit.

		$formatted = ( new DurationFormatter( $targetSchema ) )->toInt( '+2 week' );

		$this->assertEquals( (int) $expected, $formatted );

		$formatted = ( new DurationFormatter( $targetSchema ) )->toInt( '-2 week' );

		$this->assertEquals( -$expected, $formatted );
	}

	public function testConvertSchema(): void
	{
		// Convert from minutes:seconds to other format.

		$value = '10:05';

		$sourceSchema = [
			'format' => '%i:%s',
		];

		$targetSchema = [
			'format' => '%h-%i-%s',
		];

		$expected = '0-10-5';

		$formatted = ( new DurationFormatter( $targetSchema ) )->convert( $value, new DurationFormatter( $sourceSchema ) );

		$this->assertEquals( $expected, $formatted );
	}
}
