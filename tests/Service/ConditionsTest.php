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
	}
}
