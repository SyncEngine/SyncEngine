<?php

namespace SyncEngine\Tests\Runtime\Preview;

use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\PreviewRuntimeScenarioTestCase;

class ExecutePreviewValidationTest extends PreviewRuntimeScenarioTestCase
{
	public function testPreviewWithInvalidTypeReturnsError(): void
	{
		$result = $this->executePreview(
			'invalid_type',
			[
				'config' => [
					'_class' => Set::_getClassLocator(),
					'_ref'   => 'test',
					'params' => [
						[ 'key' => 'test', 'value' => '1' ],
					],
				],
			]
		);

		$this->assertFalse( $result['success'] );

		$error = $result['data']['Errors'][0];
		$this->assertArrayHasKey( 'message', $error );
		$this->assertStringContainsString( 'Invalid preview type', $error['message'] );
	}

	public function testPreviewWithEmptyTypeReturnsError(): void
	{
		$result = $this->executePreview(
			'',
			[
				'config' => [
					'_class' => Set::_getClassLocator(),
					'_ref'   => 'test',
					'params' => [
						[ 'key' => 'test', 'value' => '1' ],
					],
				],
			]
		);

		$this->assertFalse( $result['success'] );

		$error = $result['data']['Errors'][0];
		$this->assertArrayHasKey( 'message', $error );
		$this->assertStringContainsString( 'No preview type set', $error['message'] );
	}

	public function testPreviewWithMissingConfig(): void
	{
		$result = $this->executePreview(
			'task',
			[
				'config' => [],
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'Return', $result['data'] );
	}

	public function testPreviewWithMissingData(): void
	{
		$result = $this->executePreview(
			'task',
			[
				'config' => [
					'_class' => Set::_getClassLocator(),
					'_ref'   => 'no_data_task',
					'params' => [
						[ 'key' => 'test', 'value' => 'ok' ],
					],
				],
			]
		);

		$this->assertTrue( $result['success'] );
	}

	public function testPreviewWithNullVariables(): void
	{
		$result = $this->executePreview(
			'task',
			[
				'config'    => [
					'_class' => Set::_getClassLocator(),
					'_ref'   => 'null_vars_task',
					'params' => [
						[ 'key' => 'test', 'value' => 'ok' ],
					],
				],
				'variables' => null,
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'Variables', $result['data'] );
	}

	public function testPreviewWithNullScope(): void
	{
		$result = $this->executePreview(
			'task',
			[
				'config' => [
					'_class' => Set::_getClassLocator(),
					'_ref'   => 'null_scope_task',
					'params' => [
						[ 'key' => 'test', 'value' => 'ok' ],
					],
				],
				'scope'  => null,
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayNotHasKey( 'source', $result );
	}

	public function testPreviewWithAllOptionalFieldsNull(): void
	{
		$result = $this->executePreview(
			'task',
			[
				'mode'          => 'safe',
				'config'        => [
					'_class' => Set::_getClassLocator(),
					'_ref'   => 'all_null_task',
					'params' => [
						[ 'key' => 'test', 'value' => 'ok' ],
					],
				],
				'data'          => null,
				'variables'     => null,
				'requestParams' => null,
				'requestQuery'  => null,
				'scope'         => null,
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'Return', $result['data'] );
		$this->assertArrayHasKey( 'Trace', $result['data'] );
		$this->assertArrayHasKey( 'Config', $result['data'] );
		$this->assertArrayHasKey( 'Cache', $result['data'] );
		$this->assertArrayHasKey( 'Variables', $result['data'] );
	}

	public function testPreviewTraceContainsTraceData(): void
	{
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'trace_task',
				'params' => [
					[ 'key' => 'test', 'value' => '1' ],
				],
			],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertIsArray( $result['data']['Trace'] );
		$this->assertNotEmpty( $result['data']['Trace'] );
	}
}
