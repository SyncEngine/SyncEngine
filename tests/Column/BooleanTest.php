<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Service\Format\BooleanFormatter;
use SyncEngine\Tests\TestCase\BaseTestCase;

class BooleanTest extends BaseTestCase
{
	public function testFormatSchema(): void
	{
		// Basic numeric
		$value = "1";

		$targetSchema = [];

		$formatted = ( new BooleanFormatter( $targetSchema ) )->format( $value );

		$this->assertTrue( $formatted );

		// Basic text
		$value = 'true';

		$targetSchema = [];

		$formatted = ( new BooleanFormatter( $targetSchema ) )->format( $value );

		$this->assertTrue( $formatted );

		// Basic int
		$value = 1;

		$targetSchema = [];

		$formatted = ( new BooleanFormatter( $targetSchema ) )->format( $value );

		$this->assertTrue( $formatted );

		// Basic custom values
		$value = 'Bar';

		$targetSchema = [
			BooleanFormatter::FORMAT => 'custom',
			BooleanFormatter::FALSE_VALUE => 'Foo',
			BooleanFormatter::TRUE_VALUE => 'Bar',
		];

		$formatted = ( new BooleanFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'Bar', $formatted );

		$sanitized = ( new BooleanFormatter( $targetSchema ) )->sanitize( $value );

		$this->assertTrue( $sanitized );
	}
}
