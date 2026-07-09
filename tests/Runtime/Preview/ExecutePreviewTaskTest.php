<?php

namespace SyncEngine\Tests\Runtime\Preview;

use SyncEngine\Column\Numeric;
use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\PreviewRuntimeScenarioTestCase;

class ExecutePreviewTaskTest extends PreviewRuntimeScenarioTestCase
{
	public function testPreviewTaskReturnsResultInSafeMode(): void
	{
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'test_set',
				'params' => [
					[ 'key' => 'preview_value', 'value' => 'hello' ],
					[ 'key' => 'preview_number', 'value' => '42', 'column' => [ '_class' => Numeric::_getClassLocator(), 'raw' => true ] ],
				],
			],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'Return', $result['data'] );
		$this->assertSame( 'hello', $result['data']['Return']['preview_value'] );
		$this->assertSame( 42, $result['data']['Return']['preview_number'] );
		$this->assertArrayHasKey( 'Trace', $result['data'] );
		$this->assertArrayHasKey( 'Config', $result['data'] );
	}

	public function testPreviewTaskWithInputData(): void
	{
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'transform_set',
				'params' => [
					[ 'key' => 'computed', 'value' => '{{ data.input }}' ],
				],
			],
			'data' => [ 'input' => 'input_value' ],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'input_value', $result['data']['Return']['computed'] );
	}

	public function testPreviewTaskWithVariables(): void
	{
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'var_set',
				'params' => [
					[ 'key' => 'from_var', 'value' => '{{ variables.my_var }}' ],
				],
			],
			'variables' => [ 'my_var' => 'injected' ],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'injected', $result['data']['Return']['from_var'] );
	}

	public function testPreviewTaskVariablesPersistAfterExecution(): void
	{
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'persist_set',
				'params' => [
					[ 'key' => 'persisted', 'value' => 'yes' ],
				],
			],
			'variables' => [ 'initial' => 'value' ],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'Variables', $result['data'] );
		$this->assertSame( 'value', $result['data']['Variables']['initial'] );
		$this->assertSame( 'yes', $result['data']['Return']['persisted'] );
	}

	public function testPreviewTaskWithRequestParams(): void
	{
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'request_set',
				'params' => [
					[ 'key' => 'param_value', 'value' => '{{ request.query }}' ],
				],
			],
			'requestParams' => [ 'query' => 'search_term' ],
			'requestQuery' => [ 'page' => '1' ],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'search_term', $result['data']['Return']['param_value'] );
	}

	public function testPreviewTaskWithConfigParsing(): void
	{
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'parsed_set',
				'params' => [
					[ 'key' => 'dynamic', 'value' => '{{ "computed" }}' ],
				],
			],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'Config', $result['data'] );
		$this->assertArrayHasKey( 'Parsed', $result['data']['Config'] );
		$this->assertIsIterable( $result['data']['Config']['Parsed'] );
		$this->assertNull( $result['data']['Return']['dynamic'] );
	}
}
