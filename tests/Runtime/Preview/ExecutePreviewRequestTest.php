<?php

namespace SyncEngine\Tests\Runtime\Preview;

use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\PreviewRuntimeScenarioTestCase;

class ExecutePreviewRequestTest extends PreviewRuntimeScenarioTestCase
{
	public function testPreviewWithAllParametersSet(): void
	{
		$result = $this->executePreview( 'task', [
			'mode' => 'safe',
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'full_params_task',
				'params' => [
					[ 'key' => 'var_val', 'value' => '{{ variables.full_var }}' ],
					[ 'key' => 'data_val', 'value' => '{{ data.input_key }}' ],
					[ 'key' => 'param_val', 'value' => '{{ request.param_key }}' ],
					[ 'key' => 'query_val', 'value' => '{{ query.query_key }}' ],
				],
			],
			'data' => [ 'input_key' => 'input_value' ],
			'variables' => [ 'full_var' => 'var_resolved' ],
			'requestParams' => [ 'param_key' => 'param_resolved' ],
			'requestQuery' => [ 'query_key' => 'query_resolved' ],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'var_resolved', $result['data']['Return']['var_val'] );
		$this->assertSame( 'input_value', $result['data']['Return']['data_val'] );
		$this->assertSame( 'param_resolved', $result['data']['Return']['param_val'] );
		$this->assertSame( 'query_resolved', $result['data']['Return']['query_val'] );
	}

	public function testPreviewWithComplexNestedConfig(): void
	{
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'nested_task',
				'params' => [
					[
						'key' => 'nested_key',
						'value' => '{{ data.nested.deep }}',
					],
				],
			],
			'data' => [ 'nested' => [ 'deep' => 'deep_value' ] ],
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'deep_value', $result['data']['Return']['nested_key'] );
	}

	public function testPreviewWithTaskReturnCountInTitle(): void
	{
		$result = $this->executePreview( 'task', [
			'config' => [
				'_class' => Set::_getClassLocator(),
				'_ref' => 'count_task',
				'params' => [
					[ 'key' => 'one', 'value' => '1' ],
					[ 'key' => 'two', 'value' => '2' ],
				],
			],
		] );

		$this->assertTrue( $result['success'] );
		$returnKeys = array_keys( $result['data'] );
		$foundReturn = false;
		foreach ( $returnKeys as $key ) {
			if ( str_starts_with( $key, 'Return' ) ) {
				$foundReturn = true;
				break;
			}
		}
		$this->assertTrue( $foundReturn, 'Return key should exist in result data' );
	}

}
