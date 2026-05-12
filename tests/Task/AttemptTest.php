<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class AttemptTest extends TaskTestCase
{
	protected string $_task = 'Attempt';

	public function testAttempt(): void
	{
		$data = [
			'foo' => 'bar',
			'one' => 'two',
		];

		$config = [
			'actions' => [
				[
					'_class' => 'Set',
					'_ref'   => 'main_set',
					'params' => [
						[
							// This change should not be done because next task will trigger error.
							'key' => 'foo',
							'value' => 'foobar',
						],
					],
				],
				[
					'_class' => 'Send',
					'_ref'   => 'main_send',
				]
			],
			'catch' => [
				[
					'conditions' => [],
					'actions' => [
						[
							'_class' => 'Set',
							'_ref'   => 'catch_set',
							'params' => [
								[
									'key' => 'test',
									'value' => '{{ data.foo }}',
								]
							]
						]
					]
				]
			]
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( 'bar', $result['test'] );
	}

	public function testAttemptUsesMatchingCatchOption(): void
	{
		$config = [
			'actions' => [
				[
					'_class' => 'Set',
					'_ref'   => 'main_set',
					'params' => [
						[
							'key' => 'foo',
							'value' => 'foobar',
						],
					],
				],
				[
					'_class' => 'Send',
					'_ref'   => 'main_send',
				],
			],
			'catch' => [
				[
					// Disabled cases should not catch, otherwise this case would have run.
					'_disabled' => true,
					'actions' => [
						[
							'_class' => 'Set',
							'_ref'   => 'disabled_set',
							'params' => [
								[
									'key' => 'selected',
									'value' => 'disabled',
								],
							],
						],
					],
				],
				[
					// This catch won't match the error.
					'conditions' => [
						[
							'key' => 'message',
							'operator' => '==',
							'compare' => '__not_matching__',
						],
					],
					'actions' => [
						[
							'_class' => 'Set',
							'_ref'   => 'nomatch_set',
							'params' => [
								[
									'key' => 'selected',
									'value' => 'no-match',
								],
							],
						],
					],
				],
				[
					// This case will match the error and will catch it.
					'conditions' => [
						[
							'key' => 'message',
							'operator' => 'not_empty',
						],
					],
					'actions' => [
						[
							'_class' => 'Set',
							'_ref'   => 'match_set',
							'params' => [
								[
									'key' => 'selected',
									'value' => '{{ data.foo }}',
								],
							],
						],
					],
				],
			],
		];

		$result = $this->execute( $config, $this->getContext(), [
			'foo' => 'bar',
		] );

		// Data changes made before the failing action should be rolled back.
		$this->assertEquals( 'bar', $result['foo'] );
		$this->assertEquals( 'bar', $result['selected'] );
	}

	public function testAttemptUsesDefaultFallbackCatchOption(): void
	{
		$config = [
			'actions' => [
				[
					'_class' => 'Set',
					'_ref'   => 'main_set',
					'params' => [
						[
							'key' => 'foo',
							'value' => 'foobar',
						],
					],
				],
				[
					'_class' => 'Send',
					'_ref'   => 'main_send',
				],
			],
			'catch' => [
				[
					'conditions' => [
						[
							'key' => 'message',
							'operator' => '==',
							'compare' => '__not_matching__',
						],
					],
					'actions' => [
						[
							'_class' => 'Set',
							'_ref'   => 'nomatch_set',
							'params' => [
								[
									'key' => 'fallback',
									'value' => 'no-match',
								],
							],
						],
					],
				],
				[
					'conditions' => [],
					'actions' => [
						[
							'_class' => 'Set',
							'_ref'   => 'fallback_set',
							'params' => [
								[
									'key' => 'fallback',
									'value' => '{{ data.foo }}',
								],
							],
						],
					],
				],
			],
		];

		$result = $this->execute( $config, $this->getContext(), [
			'foo' => 'bar',
		] );

		$this->assertEquals( 'bar', $result['foo'] );
		$this->assertEquals( 'bar', $result['fallback'] );
	}
}
