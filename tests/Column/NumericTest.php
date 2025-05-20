<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Column\Numeric;
use SyncEngine\Tests\TestCase\ColumnTestCase;

class NumericTest extends ColumnTestCase
{
	protected string $_column = 'Numeric';

	public function getTestCases()
	{
		return [
			1,
			1.5,
			1.4999,
			21.567,
			'23,12350',
			'23,500',
			'1.213,12300',
			'5.123.456,78900000',
			'8,756,423.12345',
		];
	}

	public function testInt(): void
	{
		$column = $this->getColumn( Numeric::_getClassLocator() );

		$config = [
			'type'     => 'raw',
			'decimals' => 0,
		];

		$expected = [
			1,
			2,
			1,
			22,
			23,
			24,
			1213,
			5123457,
			8756423,
		];

		foreach ( $this->getTestCases() as $index => $test ) {
			$result = $column->format( $test, $config );
			$this->assertEquals( $expected[ $index ], $result );
		}
	}

	public function testDecimals(): void
	{
		$column = $this->getColumn( Numeric::_getClassLocator() );

		$config = [
			'type'     => 'raw',
			'decimals' => 2,
		];

		$expected = [
			1,
			1.5,
			1.5,
			21.57,
			23.12,
			23.5,
			1213.12,
			5123456.79,
			8756423.12,
		];

		foreach ( $this->getTestCases() as $index => $test ) {
			$result = $column->format( $test, $config );
			$this->assertEquals( $expected[ $index ], $result );
		}

		$config['decimals'] = 4;

		$expected = [
			1,
			1.5,
			1.4999,
			21.567,
			23.1235,
			23.5,
			1213.123,
			5123456.789,
			8756423.1235,
		];

		foreach ( $this->getTestCases() as $index => $test ) {
			$result = $column->format( $test, $config );
			$this->assertEquals( $expected[ $index ], $result );
		}
	}

	public function testDecimalsFormatted(): void
	{
		$column = $this->getColumn( Numeric::_getClassLocator() );

		$config = [
			'type'     => 'format',
			'decimals' => 2,
		];

		$expected = [
			'1.00',
			'1.50',
			'1.50',
			'21.57',
			'23.12',
			'23.50',
			'1,213.12',
			'5,123,456.79',
			'8,756,423.12',
		];

		foreach ( $this->getTestCases() as $index => $test ) {
			$result = $column->format( $test, $config );
			$this->assertEquals( $expected[ $index ], $result );
		}

		$config['decimals']            = 4;
		$config['decimal_separator']   = ',';
		$config['thousands_separator'] = '.';

		$expected = [
			'1,0000',
			'1,5000',
			'1,4999',
			'21,5670',
			'23,1235',
			'23,5000',
			'1.213,1230',
			'5.123.456,7890',
			'8.756.423,1235',
		];

		foreach ( $this->getTestCases() as $index => $test ) {
			$result = $column->format( $test, $config );
			$this->assertEquals( $expected[ $index ], $result );
		}

		$config['decimals']            = 4;
		$config['trim']                = true;
		$config['decimal_separator']   = '|';
		$config['thousands_separator'] = '';

		$expected = [
			'1',
			'1|5',
			'1|4999',
			'21|567',
			'23|1235',
			'23|5',
			'1213|123',
			'5123456|789',
			'8756423|1235',
		];

		foreach ( $this->getTestCases() as $index => $test ) {
			$result = $column->format( $test, $config );
			$this->assertEquals( $expected[ $index ], $result );
		}
	}
}
