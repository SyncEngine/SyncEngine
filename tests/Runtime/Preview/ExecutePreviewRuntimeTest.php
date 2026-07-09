<?php

namespace SyncEngine\Tests\Runtime\Preview;

use SyncEngine\Task\Cache;
use SyncEngine\Task\Retrieve;
use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\PreviewRuntimeScenarioTestCase;

class ExecutePreviewRuntimeTest extends PreviewRuntimeScenarioTestCase
{
	public function testPreviewAutomationWithRequestSource(): void
	{
		$automation = $this->createAutomationScenario( 'Preview Automation', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $this->createRoutineScenario( 'Action Routine', [
				[
					'_class' => Set::_getClassLocator(),
					'_ref' => 'action_task',
					'params' => [
						[ 'key' => 'processed', 'value' => '{{ data.input }}' ],
					],
				],
			] )->getId(),
		] );

		$result = $this->executePreview( 'automation', [
			'config' => $automation->getConfig(),
			'data' => [ 'input' => 'automation_input' ],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'automation_input', $result['data']['Return']['processed'] );
	}

	public function testPreviewAutomationWithRetrieveSource(): void
	{
		$this->mockHttpWebservice( 'MockHttp', [
			[ 'body' => [ 'retrieved' => 'data', 'id' => '123' ] ],
		] );

		$automation = $this->createAutomationScenario( 'Retrieve Automation', [
			'source' => [ 'retrieve' ],
			'retrieve' => [
				[
					'_class' => Retrieve::_getClassLocator(),
					'_ref' => 'source_retrieve',
					'connection' => [
						'_class' => 'MockHttp',
						'host' => 'https://source.test',
						'endpoint' => '/data',
						'request' => [ 'method' => 'GET' ],
						'response' => [ 'format' => 'json' ],
					],
					'response' => [ 'param' => '', 'action' => 'replace' ],
				],
			],
			'actions' => 'routine',
			'routine' => $this->createRoutineScenario( 'Post Retrieve Routine', [
				[
					'_class' => Set::_getClassLocator(),
					'_ref' => 'post_task',
					'params' => [
						[ 'key' => 'fetched_id', 'value' => '{{ data.id }}' ],
					],
				],
			] )->getId(),
		] );

		$result = $this->executePreview( 'automation', [
			'config' => $automation->getConfig(),
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( '123', $result['data']['Return']['fetched_id'] );
	}

	public function testPreviewAutomationWithFlowActions(): void
	{
		$routine = $this->createRoutineScenario( 'Flow Action Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'flow_action',
				'params' => [
					[ 'key' => 'flow_action_result', 'value' => 'done' ],
				],
			],
		] );

		$flow = $this->createFlowScenario( 'Preview Flow', [
			[ '_ref' => 'flow_step', 'routine' => $routine->getId() ],
		] );

		$automation = $this->createAutomationScenario( 'Flow Action Automation', [
			'source' => [ 'request' ],
			'actions' => 'flow',
			'flow' => $flow->getId(),
		] );

		$result = $this->executePreview( 'automation', [
			'config' => $automation->getConfig(),
			'data' => [ 'input' => 'test' ],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'done', $result['data']['Return']['flow_action_result'] );
	}

	public function testPreviewAutomationWithVariables(): void
	{
		$automation = $this->createAutomationScenario( 'Variables Automation', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $this->createRoutineScenario( 'Variable Routine', [
				[
					'_class' => Set::_getClassLocator(),
					'_ref' => 'var_task',
					'params' => [
						[ 'key' => 'auto_var', 'value' => '{{ variables.auto_var }}' ],
					],
				],
			] )->getId(),
		] );

		$result = $this->executePreview( 'automation', [
			'config' => $automation->getConfig(),
			'data' => [ 'input' => 'test' ],
			'variables' => [ 'auto_var' => 'auto_injected' ],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'auto_injected', $result['data']['Return']['auto_var'] );
	}

	public function testPreviewAutomationSourceDataAvailable(): void
	{
		$automation = $this->createAutomationScenario( 'Source Data Automation', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $this->createRoutineScenario( 'Source Routine', [
				[
					'_class' => Set::_getClassLocator(),
					'_ref' => 'source_task',
					'params' => [
						[ 'key' => 'source_key', 'value' => '{{ data.source_key }}' ],
					],
				],
			] )->getId(),
		] );

		$result = $this->executePreview( 'automation', [
			'config' => $automation->getConfig(),
			'data' => [ 'source_key' => 'source_value' ],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'source_value', $result['data']['Return']['source_key'] );
	}

	public function testPreviewAutomationCacheAcrossTasks(): void
	{
		$automation = $this->createAutomationScenario( 'Cache Automation', [
			'source' => [],
			'actions' => 'routine',
			'routine' => $this->createRoutineScenario( 'Cache Routine', [
				[
					'_class' => Cache::_getClassLocator(),
					'_ref'   => 'cache_set',
					'action' => 'set',
					'source' => 'manual',
					'manual' => 'stored',
					'tag'    => 'test',
				],
				[
					'_class' => Set::_getClassLocator(),
					'_ref' => 'cache_get',
					'params' => [
						[ 'key' => 'retrieved', 'value' => '{{ cache.test }}' ],
					],
				],
			] )->getId(),
		] );

		$result = $this->executePreview( 'automation', [
			'config' => $automation->getConfig(),
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'stored', $result['data']['Return']['retrieved'] );
	}
}
