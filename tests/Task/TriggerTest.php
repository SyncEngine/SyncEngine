<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Service\ExecuteData;
use SyncEngine\Structure\Data\ResourceData;
use SyncEngine\Tests\TestCase\TaskTestCase;

class TriggerTest extends TaskTestCase
{
	protected string $_task = 'Trigger';

	public function testTriggerNestedResourceData(): void
	{
		$context = $this->getContext();

		$config = [
			'key'        => '',
			'action'     => 'tasks',
			'pass_data' => false,
			'override_data' => false,
			'tasks' => [
				[
					'_ref' => 'testSet',
					'_class' => 'Set',
					'params' => [
						[
							'key' => 'foo.uno',
							'value' => '10',
						],
						[
							'key' => 'foo.dos',
							'value' => '20',
						],
						[
							'key' => 'test.foo.one',
							'value' => 'two',
						]
					],
				],
				[
					'_ref'   => 'subTrigger',
					'_class' => 'Trigger',
					'pass_data' => false,
					'override_data' => true,
					'key'    => 'foo',
					'action' => 'tasks',
					'tasks'  => [
						[
							'_ref' => 'testSetSub',
							'_class' => 'Set',
							'params' => [
								[
									'key' => 'foo.uno',
									'value' => '100',
								],
								[
									'key' => 'foo.dos',
									'value' => '200',
								]
							],
						],
					]
				],
			],
		];

		$data = new ExecuteData( [ 'test' => new ResourceData( [ 'foo' => new ResourceData( [ 'bar' => 'foo' ] ) ] ) ] );

		$normalized = $data->normalize();

		$returnData = $this->execute( $config, $context, $data, false );

		$this->assertEquals( $normalized, $returnData->normalize() );

		$config['pass_data'] = true;
		$config['override_data'] = false;

		$returnData = $this->execute( $config, $context, $data, false );

		// Data is passed but not overwritten, should still be unchanged.
		$this->assertEquals( $normalized, $returnData->normalize() );

		$config['pass_data'] = true;
		$config['override_data'] = true;

		$returnData = $this->execute( $config, $context, $data, false );

		// Secondary layer is not passed so should not be overwritten.
		$expected = [
			'foo' => [
				// Fully overwritten by sub Set task since it does not pass data but does override.
				'foo' => [
					'uno' => '100',
					'dos' => '200',
				],
			],
			'test' => [
				'foo' => [
					// Merged with first Set task data.
					'one' => 'two',
					'bar' => 'foo',
				]
			]
		];

		$this->assertEquals( $expected, $returnData->normalize() );
	}
}
