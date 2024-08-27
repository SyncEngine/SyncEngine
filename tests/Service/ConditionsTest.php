<?php

namespace SyncEngine\Tests\Service;

use PHPUnit\Framework\TestCase;
use SyncEngine\Service\Conditions;

class ConditionsTest extends TestCase
{
	public function testConditions(): void
	{
		$service = new Conditions();

		$conditions = [
			[
				'key'      => 'foo',
				'operator' => '==',
				'compare'  => 'bar',
			],
		];

		$data = [
			'foo' => 'bar',
		];

		$result = $service->validate( $conditions, $data );

		$this->assertTrue( $result );

		/**
		 * Not empty.
		 */
		$conditions = [
			[
				'source'   => [
					'foo' => 'bar',
				],
				'key'      => 'foo',
				'operator' => 'not_empty',
			],
			[
				'source'   => [ 1, 2, 3 ],
				'operator' => 'not_empty',
			],
		];

		$result = $service->validate( $conditions, [] );

		$this->assertTrue( $result );

		/**
		 * String contains
		 */

		//* Loose check.
		$conditions = [
			[
				'key'      => 'foo',
				'operator' => 'contains',
				'compare'  => 'bar',
			],
		];

		$result = $service->validate( $conditions, [ 'foo' => 'bar bar' ] );
		$this->assertTrue( $result );
		$result = $service->validate( $conditions, [ 'foo' => 'foobar' ] );
		$this->assertTrue( $result );

		$conditions = [
			[
				'key'      => 'foo',
				'operator' => 'contains_strict',
				'compare'  => 'bar',
			],
		];

		$result = $service->validate( $conditions, [ 'foo' => 'bar bar' ] );
		$this->assertTrue( $result );
		$result = $service->validate( $conditions, [ 'foo' => 'foobar' ] );
		$this->assertFalse( $result );
		$result = $service->validate( $conditions, [ 'foo' => 'foo_bar' ] );
		$this->assertTrue( $result );
	}
}
