<?php

namespace SyncEngine\Tests\Service;

use PHPUnit\Framework\TestCase;
use SyncEngine\Service\ConditionsValidator;
use SyncEngine\Structure\Data\ResourceData;

class ConditionsTest extends TestCase
{
	public function testConditions(): void
	{
		$service = new ConditionsValidator();

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
		 * String in
		 *
		 *  Check if the contents of data['foo'] exists within the value in 'compare'
		 */

		//* Loose check.
		$conditions = [
			[
				'key'      => 'foo',
				'operator' => 'in',
				'compare'  => 'bar bar',
			],
		];

		$result = $service->validate( $conditions, [ 'foo' => 'bar' ] );
		$this->assertTrue( $result );

		$conditions[0]['compare'] = 'foobar';
		$result = $service->validate( $conditions, [ 'foo' => 'bar' ] );
		$this->assertTrue( $result );

		$conditions = [
			[
				'key'      => 'foo',
				'operator' => 'in_strict',
				'compare'  => 'bar',
			],
		];

		$conditions[0]['compare'] = 'bar bar';
		$result = $service->validate( $conditions, [ 'foo' => 'bar' ] );
		$this->assertTrue( $result );
		$conditions[0]['compare'] = 'foobar';
		$result = $service->validate( $conditions, [ 'foo' => 'bar' ] );
		$this->assertFalse( $result );
		$conditions[0]['compare'] = 'foo_bar';
		$result = $service->validate( $conditions, [ 'foo' => 'bar' ] );
		$this->assertTrue( $result );

		/**
		 * String contains
		 *
		 * Check if the contents of data['foo'] contains the value in 'compare'
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

		/**
		 * Starts with
		 */
		$conditions = [
			[
				'key'      => 'foo',
				'operator' => '^',
				'compare'  => 'foo',
			],
		];
		$this->assertTrue( $service->validate( $conditions, [ 'foo' => 'foo_bar' ] ) );
		$this->assertFalse( $service->validate( $conditions, [ 'foo' => '_foo_bar_' ] ) );
		$this->assertTrue( $service->validate( $conditions, [ 'foo' => [ 'foo', 'test', 'bar' ] ] ) );
		$this->assertFalse( $service->validate( $conditions, [ 'foo' => [ 'bar', 'test', 'foo' ] ] ) );

		/**
		 * Ends with
		 */
		$conditions = [
			[
				'key'      => 'foo',
				'operator' => '$',
				'compare'  => 'bar',
			],
		];
		$this->assertTrue( $service->validate( $conditions, [ 'foo' => 'foo_bar' ] ) );
		$this->assertFalse( $service->validate( $conditions, [ 'foo' => '_foo_bar_' ] ) );
		$this->assertTrue( $service->validate( $conditions, [ 'foo' => [ 'foo', 'test', 'bar' ] ] ) );
		$this->assertFalse( $service->validate( $conditions, [ 'foo' => [ 'bar', 'test', 'foo' ] ] ) );

		/**
		 * NOT starts with
		 */
		$conditions = [
			[
				'key'      => 'foo',
				'operator' => '!^',
				'compare'  => 'foo',
			],
		];
		$this->assertFalse( $service->validate( $conditions, [ 'foo' => 'foo_bar' ] ) );
		$this->assertTrue( $service->validate( $conditions, [ 'foo' => '_foo_bar_' ] ) );
		$this->assertFalse( $service->validate( $conditions, [ 'foo' => [ 'foo', 'test', 'bar' ] ] ) );
		$this->assertTrue( $service->validate( $conditions, [ 'foo' => [ 'bar', 'test', 'foo' ] ] ) );

		/**
		 * NOT ends with
		 */
		$conditions = [
			[
				'key'      => 'foo',
				'operator' => '!$',
				'compare'  => 'bar',
			],
		];
		$this->assertFalse( $service->validate( $conditions, [ 'foo' => 'foo_bar' ] ) );
		$this->assertTrue( $service->validate( $conditions, [ 'foo' => '_foo_bar_' ] ) );
		$this->assertFalse( $service->validate( $conditions, [ 'foo' => [ 'foo', 'test', 'bar' ] ] ) );
		$this->assertTrue( $service->validate( $conditions, [ 'foo' => [ 'bar', 'test', 'foo' ] ] ) );
	}

	public function testConditionsEmptySource(): void
	{
		$service = new ConditionsValidator();

		$conditions = [ [ 'operator' => 'empty' ] ];
		$result = $service->validate( $conditions, [] );
		$this->assertTrue( $result );
		$result = $service->validate( $conditions, ResourceData::create( [] ) );
		$this->assertTrue( $result );
	}

	public function testConditionGroups(): void
	{
		$service = new ConditionsValidator();

		$conditions = [
			[
				// Condition group.
				'operator' => 'or',
				'conditions' => [
					[
						// Nested condition group.
						'operator' => '||', // This is an alias for 'or'.
						'conditions' => [
							[
								'key'      => 'foo',
								'operator' => '==',
								'compare'  => 'bar',
							],
							[
								'source'   => [],
								'operator' => 'not_empty', // This should be empty since the source is empty.
							],
						],
					],
					[
						'source'   => [ 1, 2, 3 ],
						'operator' => 'empty', // This should be not_empty since the source is empty.
					],
				],
			],
			[
				'source'   => [ 4, 5, 6 ],
				'operator' => 'not_empty',
			],
		];

		$data = [
			'foo' => 'bar',
		];

		$result = $service->validate( $conditions, $data );
		$this->assertTrue( $result );

		$data = [
			'foo' => 'nope',
		];
		$result = $service->validate( $conditions, $data );
		$this->assertFalse( $result );
	}
}
