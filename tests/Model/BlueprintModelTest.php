<?php

namespace SyncEngine\Tests\Model;

use SyncEngine\Model\BlueprintModel;
use SyncEngine\Tests\Structure\Data\ConfigDataTest;
use SyncEngine\Tests\TestCase\BaseTestCase;

class BlueprintModelTest extends BaseTestCase
{
	/**
	 * @see ConfigDataTest::testSanitizeConfigRecursive
	 */
	public function testBlueprintClearConfig(): void
	{
		$blueprint = new BlueprintModel(
			[
				'version' => '1.0',
				'author' => '',
				'type' => '',
				'entity' => 'routine',
				'name' => 'Test',
				'description' => 'Test',
				'fields' => [
					'foo'  => [
						'type' => 'text',
					],
					'bar'  => [
						'type'   => 'text',
						'fields' => [
							'one'   => [
								'type' => 'text',
							],
							'two'   => [
								'type'       => 'text',
								'conditions' => [ 'one' => 1 ],
							],
							'three' => [
								'type'       => 'text',
								'conditions' => [ 'two' => 2 ],
							],
						],
					],
					'nope' => [
						'type' => 'text',
					],
				],
				'template' => [
					[]
				],
			]
		);

		$config = [
			'_blueprint' => [
				'foo'   => 'bar',
				'bar'   => '',
				'one'   => 'one',
				'two'   => 'two',
				'three' => 'three',
				'nope'  => false,
			],
			'foo'   => 'bar',
			'bar'   => '',
			'one'   => 'one',
			'two'   => 'two',
			'three' => 'three',
			'nope'  => false,
		];

		$expected = [
			'_blueprint' => [
				'foo'   => 'bar',
				'bar'   => '',
				'one'   => 'one',
				'nope'  => false,
			],
		];

		$sanitized = $blueprint->clearConfig( $config );

		$this->assertEquals( $expected, $sanitized );
	}
}
