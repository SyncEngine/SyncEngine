<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Service\Format\CurrencyFormatter;
use SyncEngine\Tests\TestCase\ColumnTestCase;

class CurrencyTest extends ColumnTestCase
{
	protected string $_column = 'Currency';

	public function testFormatSchema(): void
	{
		// No decimals
		$value = 12;

		$formatted = ( new CurrencyFormatter( [] ) )->format( $value );

		$this->assertEquals( '12', $formatted );

		// Remove decimals and convert type.
		$targetSchema = [
			'decimals' => 1,
			'decimal_separator' => ',',
			'thousands_separator' => '.',
		];

		$formatted = ( new CurrencyFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( '12,0', $formatted );
	}

	public function testFormatSchemaDecimals(): void
	{
		// Remove decimals and convert type.
		$value = 12.34;

		$targetSchema = [
			'decimals' => 1,
			'decimal_separator' => ',',
			'thousands_separator' => '.',
		];

		$formatted = ( new CurrencyFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( '12,3', $formatted );

		// Rounding up.
		$value = 11.5;

		$targetSchema = [
			'round' => 'up',
			'decimals' => 0,
			'decimal_separator' => ',',
			'thousands_separator' => '.',
			'decimal_dash' => true,
		];

		$formatted = ( new CurrencyFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( '12,-', $formatted );

		// Rounding down.
		$value = 12.5;

		$targetSchema = [
			'round' => 'down',
			'decimals' => 0,
			'decimal_separator' => ',',
			'thousands_separator' => '.',
			'decimal_dash' => true,
		];

		$formatted = ( new CurrencyFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( '12,-', $formatted );

		// Currency prefix
		$value = '12.356,125';

		$targetSchema = [
			'round' => 'down',
			'decimals' => 2,
			'currency_sign' => '€',
			'currency_space' => true,
			'thousands_separator' => '\'',
		];

		$formatted = ( new CurrencyFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( '€ 12\'356.12', $formatted );

		// Currency postfix + dash
		$value = '12.3512356,125';

		$targetSchema = [
			'round' => 'down',
			'decimals' => 0,
			'decimal_dash' => true,
			'currency_sign' => 'US$',
			'currency_space' => true,
			'currency_after' => true,
			'thousands_separator' => ' ',
		];

		$formatted = ( new CurrencyFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( '123 512 356.- US$', $formatted );
	}
}
