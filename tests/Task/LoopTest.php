<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Service\ExecuteData;
use SyncEngine\Tests\TestCase\TaskTestCase;

class LoopTest extends TaskTestCase
{
	protected string $_task = 'Loop';

	public function testLoop(): void
	{
		$context = $this->getContext();

		$config = [
			'key'        => '',
			'action'     => 'tasks',
			'tasks' => [
				[
					'_ref' => 'test',
					'_class' => 'Trace',
					'message' => 'Current Index: {{ context.loop.index }}',
					'info' => '{{ context.loop }}'
				],
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
						]
					],
				],
				[
					'_ref'   => 'subLoop',
					'_class' => 'Loop',
					'key'    => 'foo',
					'action' => 'tasks',
					'tasks'  => [
						[
							'_ref' => 'testasd',
							'_class' => 'Trace',
							'message' => 'Current Index: {{ context.loop.index }}',
							'info' => '{{ context.loop }}'
						],
					]
				],
				[
					'_ref' => 'test',
					'_class' => 'Trace',
					// Should fallback to previous loop context.
					'message' => 'Current Index: {{ context.loop.index }}',
					'info' => '{{ context.loop }}'
				],
			],
		];

		$data = [
			'one' => [ 1, '1' ],
			'two' => [ 2, '2' ],
			'three' => [ 3, '3' ],
		];

		$expected = [
			'one' => [ 1, '1', 'foo' => [ 'uno' => '10', 'dos' => '20' ] ],
			'two' => [ 2, '2', 'foo' => [ 'uno' => '10', 'dos' => '20' ] ],
			'three' => [ 3, '3', 'foo' => [ 'uno' => '10', 'dos' => '20' ] ],
		];

		$returnData = $this->execute( $config, $context, $data );

		$this->assertEquals( $expected, ExecuteData::create( $returnData )->normalize() );

		$logs = $context->getLogs();
		foreach ( $logs as &$log ) {
			$log = (string) $log;
		}

		$expected = [
			'Current Index: one', // main.
			'Current Index: uno',
			'Current Index: dos',
			'Current Index: one', // main.
			'Current Index: two', // main.
			'Current Index: uno',
			'Current Index: dos',
			'Current Index: two', // main.
			'Current Index: three', // main.
			'Current Index: uno',
			'Current Index: dos',
			'Current Index: three', // main.
		];

		$this->assertEquals( $expected, $logs );
	}
}
