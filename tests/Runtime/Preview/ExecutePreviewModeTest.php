<?php

namespace SyncEngine\Tests\Runtime\Preview;

use SyncEngine\Task\Send;
use SyncEngine\Task\Set;
use SyncEngine\Task\Trigger;
use SyncEngine\Tests\TestCase\PreviewRuntimeScenarioTestCase;

class ExecutePreviewModeTest extends PreviewRuntimeScenarioTestCase
{
	public function testPreviewSafeModeDefault(): void
	{
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Send::_getClassLocator(),
				'_ref' => 'safe_task',
				'connection' => null,
			],
		] );

		// Send task is skipped, so it should not be executed and therefore throw no errors.
		$this->assertTrue( $result['success'] );
	}

	public function testPreviewExplicitSafeMode(): void
	{
		$result = $this->executePreview( 'task', [
			'mode' => 'safe',
			'config' => [
				'_class' => Send::_getClassLocator(),
				'_ref' => 'explicit_safe',
				'connection' => null,
			],
		] );

		$this->assertTrue( $result['success'] );
	}

	public function testPreviewLiveMode(): void
	{
		$result = $this->executePreview( 'task', [
			'mode' => 'live',
			'config' => [
				'_class' => Send::_getClassLocator(),
				'_ref' => 'live_task',
				'connection' => null,
			],
		] );

		// Send task is not skipped, so it should be executed and therefore throw an error because the connection is not set.
		$this->assertFalse( $result['success'] );

		$message = $result['data']['Errors'][0]['message'];
		$this->assertStringContainsString( 'No connection configured', $message );
	}

	public function testPreviewTaskWithDisabledFlag(): void
	{
		// When testing a task with the disabled flag, we expect the task to run anyway.
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'disabled_task',
				'_disabled' => true,
				'params' => [
					[ 'key' => 'should_not_set', 'value' => 'value' ],
				],
			],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'should_not_set', $result['data']['Return'] );

		// However, subtasks that are disabled should not be executed.
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Trigger::_getClassLocator(),
				'_ref' => 'trigger_disabled_task',
				'action' => 'tasks',
				'pass_data' => true,
				'override_data' => true,
				'tasks' => [
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'disabled_task',
						'_disabled' => true,
						'params' => [
							[ 'key' => 'should_not_set', 'value' => 'value' ],
						],
					],
					[
						'_class' => Set::_getClassLocator(),
						'_ref' => 'enabled_task',
						'params' => [
							[ 'key' => 'should_be_set', 'value' => 'value' ],
						],
					]
				],
			],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertArrayNotHasKey( 'should_not_set', $result['data']['Return'] );
		$this->assertArrayHasKey( 'should_be_set', $result['data']['Return'] );
	}
}
