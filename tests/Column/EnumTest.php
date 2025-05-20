<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Column\Enum;
use SyncEngine\Exception\InvalidValueException;
use SyncEngine\Tests\TestCase\ColumnTestCase;

class EnumTest extends ColumnTestCase
{
	protected string $_column = 'Enum';

	public function testEnum()
	{
		$column = $this->getColumn( Enum::_getClassLocator() );

		$config = [
			'options' => [
				[
					'value' => 'one',
					'label' => 'One',
				],
				[
					'value' => 'two',
					'label' => 'Two',
				],
				[
					'value' => 'three',
					'label' => 'Three',
				],
			],
		];

		// No validation or fallback.
		$this->assertEquals( 'one', $column->format( 'one', $config ) );
		$this->assertEquals( 'two', $column->format( 'two', $config ) );
		$this->assertEquals( 'three', $column->format( 'three', $config ) );
		$this->assertEquals( '', $column->format( '', $config ) );
		$this->assertEquals( 'other', $column->format( 'other', $config ) );

		$config['fallback'] = 'one';

		// No validation but has fallback.
		$this->assertEquals( 'one', $column->format( 'one', $config ) );
		$this->assertEquals( 'two', $column->format( 'two', $config ) );
		$this->assertEquals( 'three', $column->format( 'three', $config ) );
		$this->assertEquals( 'one', $column->format( '', $config ) ); // << Default.
		$this->assertEquals( 'other', $column->format( 'other', $config ) ); // Not empty.

		$config['fallback'] = 'one';
		$config['mode']     = 'soft';

		// No validation but has fallback.
		$this->assertEquals( 'one', $column->format( 'one', $config ) );
		$this->assertEquals( 'two', $column->format( 'two', $config ) );
		$this->assertEquals( 'three', $column->format( 'three', $config ) );
		$this->assertEquals( 'one', $column->format( '', $config ) ); // << Default.
		$this->assertEquals( 'one', $column->format( 'other', $config ) ); // << Default in soft mode.

		/**
		 * Strict mode
		 */
		$config['fallback'] = 'one';
		$config['mode']     = 'strict';

		// No validation but has fallback.
		$this->assertEquals( 'one', $column->format( 'one', $config ) );
		$this->assertEquals( 'two', $column->format( 'two', $config ) );
		$this->assertEquals( 'three', $column->format( 'three', $config ) );

		// Empty values should still revert to fallback.
		$this->assertEquals( 'one', $column->format( '', $config ) );

		// Invalid values should trigger InvalidValueException.
		$triggeredError = false;
		try {
			$column->format( 'other', $config );
		} catch ( InvalidValueException $e ) {
			$triggeredError = true;
		}
		$this->assertTrue( $triggeredError );
	}
}
