<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class CalculateTest extends TaskTestCase
{
	protected string $_task = 'Calculate';

	public function testCalculations(): void
	{
		$data = [
			'total'    => '12.50',
			'quantity' => 2,
			'lines'    => [ [ 'qty' => 3 ], [ 'qty' => 4 ] ],
		];

		$result = $this->execute( [
			'calculations' => [
				[ 'key' => 'unit_price', 'expression' => 'data.total / data.quantity', 'decimals' => 2 ],
				// Later rows can use earlier results.
				[ 'key' => 'with_vat', 'expression' => 'data.unit_price * 1.21', 'decimals' => 2 ],
				[ 'key' => 'first_qty', 'expression' => 'data.lines[0].qty' ],
				[ 'key' => 'max_qty', 'expression' => 'max(data.lines[0].qty, data.lines[1].qty)' ],
				[ 'key' => 'nested.value', 'expression' => '(data.missing ?? 10) + 1' ],
			],
		], null, $data );

		$this->assertSame( 6.25, $result['unit_price'] );
		$this->assertSame( 7.56, $result['with_vat'] );
		$this->assertSame( 3, $result['first_qty'] );
		$this->assertSame( 4, $result['max_qty'] );
		$this->assertSame( 11, $result['nested']['value'] );
		$this->assertSame( '12.50', $result['total'] );
	}

	public function testScopedKeyAndVariables(): void
	{
		$context = $this->getContext()->descend( null, [ 'rate' => 3 ] );

		$result = $this->execute( [
			'key'          => 'order',
			'calculations' => [
				[ 'key' => 'total', 'expression' => 'data.price * variables.rate' ],
			],
		], $context, [ 'order' => [ 'price' => 5 ] ] );

		$this->assertSame( 15, $result['order']['total'] );
	}

	public function testErrorsAreCollectedAndUnsafeFunctionsUnavailable(): void
	{
		$context = $this->getContext()->descend( null );

		$result = $this->execute( [
			'calculations' => [
				[ 'key' => 'div', 'expression' => 'data.a / 0' ],
				[ 'key' => 'const', 'expression' => 'constant("PHP_VERSION")' ],
				[ 'key' => 'ok', 'expression' => 'data.a + 1' ],
			],
		], $context, [ 'a' => 1 ] );

		$this->assertArrayNotHasKey( 'div', $result );
		$this->assertArrayNotHasKey( 'const', $result );
		$this->assertSame( 2, $result['ok'] );
		$this->assertCount( 2, $context->getErrors() );
	}
}
