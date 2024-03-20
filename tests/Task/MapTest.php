<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class MapTest extends TaskTestCase
{
	protected string $_task = 'Map';

	public function testMapKey(): void
	{
		$data = [
			'name'  => 'Test',
			'price' => 12.34,
		];

		$config = [
			'action' => 'key',
			'map'    => [
				'manual' => [
					[
						'source' => 'price',
						'target' => 'new_price',
					],
				],
			],
		];

		// Default.

		$expected = [
			'name'      => 'Test',
			'price'     => 12.34,
			'new_price' => 12.34,
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Remove old keys.

		$config['remove_keys'] = true;

		$expected = [
			'name'      => 'Test',
			'new_price' => 12.34,
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Mapped only

		$config['mapped_only'] = true;

		$expected = [
			'new_price' => 12.34,
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Nested arrays.

		$config['map'] = [
			'manual' => [
				[
					'source' => 'relationships.list.ids',
					'target' => 'relations',
				],
			],
		];
		$data          = [
			'relationships' => [
				'list' => [
					'ids' => [ 1, 2, 3 ],
				],
			],
		];
		$expected      = [
			'relations' => [ 1, 2, 3 ],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testMapValue(): void
	{
		$data = [
			'name'  => 'Test',
			'price' => 12.34,
		];

		$config = [
			'action' => 'value',
			'key'    => 'name',
			'map'    => [
				'manual' => [
					[
						// Only replaces exact matches. For partials, use Replace task.
						'source' => 'Test',
						'target' => 'Testing',
					],
				],
			],
		];

		// Default.

		$expected = [
			'name'  => 'Testing',
			'price' => 12.34,
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Key.

		$data['groups'] = [ 'Sync', 'Motor' ];

		$config['key'] = 'groups';
		$config['map'] = [
			'manual' => [
				[
					'source' => 'Motor',
					'target' => 'Engine',
				],
			],
		];

		$expected = [
			'name'   => 'Test',
			'price'  => 12.34,
			'groups' => [ 'Sync', 'Engine' ],
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}

	public function testConvertSchema(): void
	{
		// Base config.
		$config = [
			'map'    => [
				'schema' => [
					'source' => [],
					'target' => [],
				],
				'manual' => [
					[
						'source' => 'price',
						'target' => 'price',
					],
				],
			],
		];

		// Remove decimals and convert type.
		$config['map']['schema']['target']['price'] = [
			'_class'              => 'Numeric',
			'type'                => 'format',
			'decimals'            => 1,
			'decimal_separator'   => ',',
			'thousands_separator' => '.',
		];

		$data = [
			'price' => 12.34,
		];

		$expected = [
			'price' => '12,3',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Remove decimals and convert type.
		$config['map']['schema']['target']['price'] = [
			'_class'              => 'Numeric',
			'type'                => 'format',
			'decimals'            => 2,
			'decimal_separator'   => ',',
			'thousands_separator' => '.',
		];

		$data = [
			'price' => '12.340000',
		];

		$expected = [
			'price' => '12,34',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Rounding up.
		$config['map']['schema']['target']['price'] = [
			'_class'              => 'Numeric',
			'type'                => 'format',
			'decimals'            => 1,
			'decimal_separator'   => ',',
			'thousands_separator' => '.',
		];

		$data = [
			'price' => 12.35,
		];

		$expected = [
			'price' => '12,4',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Rounding down.
		$config['map']['schema']['target']['price'] = [
			'_class'              => 'Numeric',
			'type'                => 'format',
			'round'               => 'down',
			'decimals'            => 1,
			'decimal_separator'   => ',',
			'thousands_separator' => '.',
		];

		$data = [
			'price' => 12.75,
		];

		$expected = [
			'price' => '12,7',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Convert schema + round down.
		$config['map']['schema']['target']['price'] = [
			'_class'   => 'Numeric',
			'type'     => 'raw',
			'round'    => 'down',
			'decimals' => 2,
		];

		$data = [
			'price' => '12.356,125',
		];

		$expected = [
			'price' => 12356.12,
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );

		// Convert schema + detect decimals
		$config['map']['schema']['target']['price'] = [
			'_class'              => 'Numeric',
			'type'                => 'format',
			'round'               => 'down',
			'decimals'            => 1,
			'decimal_separator'   => '.',
			'thousands_separator' => ' ',
		];

		$data = [
			'price' => '12,356,-',
		];

		$expected = [
			'price' => '12 356.0',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}
}
