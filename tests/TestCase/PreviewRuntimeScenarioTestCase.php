<?php

namespace SyncEngine\Tests\TestCase;

use SyncEngine\Runtime\ExecutePreview;

class PreviewRuntimeScenarioTestCase extends RuntimeScenarioTestCase
{
	/**
	 * Execute a preview for the given type with the provided config and parameters.
	 *
	 * @param  string  $type  Preview type: task, routine, step, flow, automation
	 * @param  array  $params  Preview parameters: config, data, variables, requestParams, requestQuery, scope
	 *
	 * @return array<string, mixed>
	 */
	protected function executePreview( string $type, array $params = [] ): array
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );

		return $executePreview->preview(
			type: $type,
			mode: $params['mode'] ?? ExecutePreview::MODE_SAFE,
			config: $params['config'] ?? [],
			data: $params['data'] ?? [],
			variables: $params['variables'] ?? null,
			requestParams: $params['requestParams'] ?? null,
			requestQuery: $params['requestQuery'] ?? null,
			scope: $params['scope'] ?? null,
		);
	}

	public function getLastErrorMessage( $result ): string
	{
		if ( isset( $result['data']['Errors'][0]['message'] ) ) {
			return $result['data']['Errors'][0]['message'];
		}

		return parent::getLastErrorMessage( $result );
	}
}
