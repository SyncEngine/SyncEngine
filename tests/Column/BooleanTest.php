<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Column\Boolean;
use SyncEngine\Service\Format\BooleanFormatter;
use SyncEngine\Tests\TestCase\ColumnTestCase;

class BooleanTest extends ColumnTestCase
{
	/**
	 * @see Boolean
	 */
	protected string $_column = 'Boolean';

	public function testFormatSchema(): void
	{
		// Test basic values
		$this->assertFormattedTrue('1');
		$this->assertFormattedTrue('true');
		$this->assertFormattedFalse(0);
		$this->assertFormattedFalse(null);
		$this->assertFormattedFalse(-1);
		$this->assertFormattedFalse(['true']);
		$this->assertFormattedTrue(1);

		// Test custom values
		$customSchema = [
			BooleanFormatter::FORMAT => 'custom',
			BooleanFormatter::FALSE_VALUE => 'Foo',
			BooleanFormatter::TRUE_VALUE => 'Bar',
		];

		$this->assertFormattedCustom('Bar', $customSchema);
	}

	private function assertFormattedTrue($value): void
	{
		$targetSchema = [];

		$formatted = (new BooleanFormatter($targetSchema))->format($value);
		$this->assertTrue($formatted);
	}

	private function assertFormattedFalse($value): void
	{
		$targetSchema = [];
		$formatted = (new BooleanFormatter($targetSchema))->format($value);

		$this->assertNotTrue($formatted);
	}

	private function assertFormattedCustom($value, array $targetSchema): void
	{
		$formatter = new BooleanFormatter($targetSchema);

		// Check if format works
		$formatted = $formatter->format($value);
		$this->assertEquals('Bar', $formatted);

		// Check if sanitize works
		$sanitized = $formatter->sanitize($value);
		$this->assertTrue($sanitized);
	}
}
