<?php

namespace SyncEngine\Tests\Service;

use PHPUnit\Framework\TestCase;
use SyncEngine\Exception\InvalidConfigException;
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

	/**
	 * Test the new top-level 'conditions' key format used by the frontend.
	 * This format wraps groups under a 'conditions' key with a top-level 'operator'.
	 */
	public function testConditionGroupsTopLevelConditionsKey(): void
	{
		$service = new ConditionsValidator();

		// Top-level conditions key format.
		$conditions = [
			'conditions' => [
				[
					'operator' => 'AND',
					'conditions' => [
						[
							'key'      => 'status',
							'operator' => '==',
							'compare'  => 'active',
						],
					],
				],
			],
			'operator' => 'AND',
		];

		$data = [
			'status' => 'active',
		];

		// Should validate because the single group matches.
		$result = $service->validate( $conditions['conditions'], $data, $conditions['operator'] );
		$this->assertTrue( $result );

		$data = [
			'status' => 'inactive',
		];
		$result = $service->validate( $conditions['conditions'], $data, $conditions['operator'] );
		$this->assertFalse( $result );
	}

	/**
	 * Test top-level AND operator: all groups must match.
	 */
	public function testConditionGroupsTopLevelAND(): void
	{
		$service = new ConditionsValidator();

		$conditions = [
			[
				'operator' => 'AND',
				'conditions' => [
					[ 'key' => 'status', 'operator' => '==', 'compare' => 'active' ],
				],
			],
			[
				'operator' => 'AND',
				'conditions' => [
					[ 'key' => 'type', 'operator' => '==', 'compare' => 'user' ],
				],
			],
		];

		$data = [
			'status' => 'active',
			'type'   => 'user',
		];
		$this->assertTrue( $service->validate( $conditions, $data, 'AND' ) );

		$data = [
			'status' => 'active',
			'type'   => 'admin',
		];
		$this->assertFalse( $service->validate( $conditions, $data, 'AND' ) );

		$data = [
			'status' => 'inactive',
			'type'   => 'user',
		];
		$this->assertFalse( $service->validate( $conditions, $data, 'AND' ) );
	}

	/**
	 * Test top-level OR operator: any group can match.
	 */
	public function testConditionGroupsTopLevelOR(): void
	{
		$service = new ConditionsValidator();

		$conditions = [
			[
				'operator' => 'AND',
				'conditions' => [
					[ 'key' => 'status', 'operator' => '==', 'compare' => 'active' ],
				],
			],
			[
				'operator' => 'AND',
				'conditions' => [
					[ 'key' => 'type', 'operator' => '==', 'compare' => 'admin' ],
				],
			],
		];

		// First group matches.
		$data = [ 'status' => 'active', 'type' => 'user' ];
		$this->assertTrue( $service->validate( $conditions, $data, 'OR' ) );

		// Second group matches.
		$data = [ 'status' => 'inactive', 'type' => 'admin' ];
		$this->assertTrue( $service->validate( $conditions, $data, 'OR' ) );

		// Neither group matches.
		$data = [ 'status' => 'inactive', 'type' => 'user' ];
		$this->assertFalse( $service->validate( $conditions, $data, 'OR' ) );
	}

	/**
	 * Test invalid group handling: groups with no conditions should be skipped.
	 */
	public function testConditionGroupsInvalidGroup(): void
	{
		$service = new ConditionsValidator();

		$conditions = [
			[
				'operator' => 'AND',
				'conditions' => true,
			],
			[
				'operator' => 'AND',
				'conditions' => [
					[ 'key' => 'status', 'operator' => '==', 'compare' => 'active' ],
				],
			],
		];

		$exception = null;
		try {
			$service->validate( $conditions, [], 'AND' );
		} catch ( \Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( InvalidConfigException::class, $exception );
	}

	/**
	 * Test empty group handling: groups with no conditions should be skipped.
	 */
	public function testConditionGroupsEmptyGroup(): void
	{
		$service = new ConditionsValidator();

		$conditions = [
			[
				'operator' => 'AND',
				'conditions' => [],
			],
			[
				'operator' => 'AND',
				'conditions' => [
					[ 'key' => 'status', 'operator' => '==', 'compare' => 'active' ],
				],
			],
		];

		$data = [ 'status' => 'active' ];
		$this->assertTrue( $service->validate( $conditions, $data, 'AND' ) );

		$data = [ 'status' => 'inactive' ];
		$this->assertFalse( $service->validate( $conditions, $data, 'AND' ) );
	}

	/**
	 * Test single group with non-AND operator: should validate correctly.
	 */
	public function testConditionGroupsSingleNonAndGroup(): void
	{
		$service = new ConditionsValidator();

		$conditions = [
			[
				'operator' => 'OR',
				'conditions' => [
					[ 'key' => 'status', 'operator' => '==', 'compare' => 'active' ],
					[ 'key' => 'status', 'operator' => '==', 'compare' => 'pending' ],
				],
			],
		];

		$data = [ 'status' => 'active' ];
		$this->assertTrue( $service->validate( $conditions, $data, 'AND' ) );

		$data = [ 'status' => 'pending' ];
		$this->assertTrue( $service->validate( $conditions, $data, 'AND' ) );

		$data = [ 'status' => 'inactive' ];
		$this->assertFalse( $service->validate( $conditions, $data, 'AND' ) );
	}

	/**
	 * Test mixed operators: AND group + OR group combined with OR at top level.
	 */
	public function testConditionGroupsMixedOperators(): void
	{
		$service = new ConditionsValidator();

		$conditions = [
			[
				'operator' => 'AND',
				'conditions' => [
					[ 'key' => 'status', 'operator' => '==', 'compare' => 'active' ],
					[ 'key' => 'type', 'operator' => '==', 'compare' => 'user' ],
				],
			],
			[
				'operator' => 'OR',
				'conditions' => [
					[ 'key' => 'role', 'operator' => '==', 'compare' => 'admin' ],
					[ 'key' => 'role', 'operator' => '==', 'compare' => 'superadmin' ],
				],
			],
		];

		// First group matches (AND: both conditions true).
		$data = [ 'status' => 'active', 'type' => 'user', 'role' => 'guest' ];
		$this->assertTrue( $service->validate( $conditions, $data, 'OR' ) );

		// Second group matches (OR: role is admin).
		$data = [ 'status' => 'inactive', 'type' => 'guest', 'role' => 'admin' ];
		$this->assertTrue( $service->validate( $conditions, $data, 'OR' ) );

		// Neither group matches.
		$data = [ 'status' => 'inactive', 'type' => 'guest', 'role' => 'guest' ];
		$this->assertFalse( $service->validate( $conditions, $data, 'OR' ) );
	}

	/**
	 * Test deep nesting: outer OR → middle AND → inner OR with conditions.
	 */
	public function testConditionGroupsDeepNesting(): void
	{
		$service = new ConditionsValidator();

		$conditions = [
			[
				'operator' => 'OR',
				'conditions' => [
					[
						'operator' => 'AND',
						'conditions' => [
							[ 'key' => 'a', 'operator' => '==', 'compare' => '1' ],
							[
								'operator' => 'OR',
								'conditions' => [
									[ 'key' => 'b', 'operator' => '==', 'compare' => '2' ],
									[ 'key' => 'c', 'operator' => '==', 'compare' => '3' ],
								],
							],
						],
					],
					[
						'operator' => 'AND',
						'conditions' => [
							[ 'key' => 'd', 'operator' => '==', 'compare' => '4' ],
						],
					],
				],
			],
		];

		// First branch: a=1 AND (b=2 OR c=3).
		$data = [ 'a' => '1', 'b' => '2', 'c' => 'x' ];
		$this->assertTrue( $service->validate( $conditions, $data, 'AND' ) );

		$data = [ 'a' => '1', 'b' => 'x', 'c' => '3' ];
		$this->assertTrue( $service->validate( $conditions, $data, 'AND' ) );

		// First branch fails (a != 1), second branch matches (d=4).
		$data = [ 'a' => 'x', 'b' => 'x', 'c' => 'x', 'd' => '4' ];
		$this->assertTrue( $service->validate( $conditions, $data, 'OR' ) );

		// Nothing matches.
		$data = [ 'a' => 'x', 'b' => 'x', 'c' => 'x', 'd' => 'x' ];
		$this->assertFalse( $service->validate( $conditions, $data, 'OR' ) );
	}
}
