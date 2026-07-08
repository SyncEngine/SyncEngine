<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Runtime\ExecutePreview;
use SyncEngine\Service\Sandbox\EntityManagerSandbox;

class SandboxController extends DefaultController
{
	const VALID_TYPES = [ 'task', 'routine', 'step', 'flow', 'automation' ];

	public static function getSubscribedServices(): array
	{
		return array_merge(
			parent::getSubscribedServices(),
			[
				'entitymanager' => '?' . EntityManagerSandbox::class,
			]
		);
	}

	#[Route( '/json/preview', name: 'json_preview', requirements: [] )]
	public function preview( ExecutePreview $executePreview, Request $request = null ): JsonResponse
	{
		return $this->json( $executePreview->preview( $request ) );
	}

	#[Route( '/api/preview', name: 'api_preview', requirements: [] )]
	public function api_preview( ExecutePreview $executePreview, Request $request = null ): JsonResponse
	{
		return $this->preview( $executePreview, $request );
	}

	#[Route('/api/preview/schema', name: 'preview_schema', methods: [ 'GET' ])]
	public function preview_schema(): JsonResponse
	{
		return $this->json( $this->getPreviewSchema() );
	}

	protected function getPreviewSchema(): array
	{
		return [
			'type' => [
				'type' => 'string',
				'description' => 'Preview type (task, routine, step, flow, automation).',
				'enum' => self::VALID_TYPES,
				'default' => '',
			],
			'mode' => [
				'type' => 'string',
				'description' => 'Preview mode: "safe" (dry-run, does not execute send-type webservice calls) or "live" (executes all webservice calls). Default: safe.',
				'enum' => [ 'safe', 'live' ],
				'default' => 'safe',
			],
			'data' => [
				'type' => 'any',
				'description' => 'Optional data to be used for preview as the source data.',
			],
			'config' => [
				'type' => 'object',
				'description' => 'Configuration object specific to the preview type.',
			],
			'variables' => [
				'type' => 'object',
				'description' => 'Key-value pairs of variables available during execution.',
				'additionalProperties' => [ 'type' => 'string' ],
			],
			'requestParams' => [
				'type' => 'object',
				'description' => 'Simulated request body parameters.',
				'additionalProperties' => true,
			],
			'requestQuery' => [
				'type' => 'object',
				'description' => 'Simulated request query string parameters.',
				'additionalProperties' => true,
			],
			'scope' => [
				'type' => 'array',
				'description' => 'Optional queue of pre-configured entity models to simulate a full pipeline. Each item: { _entity, id, config }.',
			],
		];
	}
}
