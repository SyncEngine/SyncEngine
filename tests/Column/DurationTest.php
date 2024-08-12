<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Service\Format\DurationFormatter;
use SyncEngine\Tests\TestCase\BaseTestCase;

class DurationTest extends BaseTestCase
{
	public function testFormatSchema(): void
	{
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
	}
}
