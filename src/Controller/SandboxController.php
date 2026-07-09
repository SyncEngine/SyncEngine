<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

	#[Route( '/json/preview', name: 'json_preview', requirements: [], methods: [ 'POST' ] )]
	public function preview( Request $request, ExecutePreview $executePreview ): JsonResponse
	{
		return $this->handlePreview( $request, $executePreview );
	}

	#[Route( '/api/preview', name: 'api_preview', requirements: [], methods: [ 'POST' ] )]
	public function api_preview( Request $request, ExecutePreview $executePreview ): JsonResponse
	{
		return $this->handlePreview( $request, $executePreview );
	}

	#[Route( '/api/preview/schema/{type}', name: 'preview_schema', requirements: [ 'type' => '[a-z]+' ], methods: [ 'GET' ] )]
	public function preview_schema( string $type ): JsonResponse
	{
		return $this->json( $this->getPreviewSchema( $type ) );
	}

	protected function handlePreview( Request $request, ExecutePreview $executePreview ): JsonResponse
	{
		try {
			$body = $request->toArray();
		} catch ( \Throwable $e ) {
			return $this->json(
				[
					'success'     => false,
					'message'     => 'Invalid JSON request body.',
				],
				Response::HTTP_BAD_REQUEST
			);
		}

		try {
			$type          = $body['type'] ?? '';
			$mode          = $body['mode'] ?? ExecutePreview::MODE_SAFE;
			$config        = $body['config'] ?? [];
			$data          = $body['data'] ?? [];
			$variables     = $body['variables'] ?? null;
			$requestParams = $body['requestParams'] ?? null;
			$requestQuery  = $body['requestQuery'] ?? null;
			$scope         = $body['scope'] ?? null;

			if ( ! in_array( $type, self::VALID_TYPES, true ) ) {
				return $this->json(
					[
						'success'     => false,
						'message'     => 'Invalid preview type. Valid types: ' . implode( ', ', self::VALID_TYPES ),
						'valid_types' => self::VALID_TYPES,
					],
					Response::HTTP_BAD_REQUEST
				);
			}

			if ( is_string( $data ) ) {
				try {
					$parsedData = json_decode( $data, true, flags: JSON_THROW_ON_ERROR );
					$data       = is_array( $parsedData ) ? $parsedData : $data;
				} catch ( \Throwable $e ) {
					// Nope.
				}
			}

			$result = $executePreview->preview(
				type         : $type,
				mode         : $mode,
				config       : $config,
				data         : $data,
				variables    : $variables,
				requestParams: $requestParams,
				requestQuery : $requestQuery,
				scope        : $scope,
			);

			$count = isset( $result['Return'] ) ? count( is_countable( $result['Return'] ) ? $result['Return'] : [] ) : null;
			if ( $count ) {
				// @todo Implement tab/pane style DTO for return tabs.
				$result['Return (' . $count . ')' ] = $result['Return'];
				unset( $result['Return'] );
			}

			$params = $request->request->all();
			foreach ( $params as &$param ) {
				try {
					$param = json_decode( $param, true ) ?: $param;
				} catch ( \Throwable $e ) {
					// Nope.
				}
			}
			$result['Params'] = $params;

			$status = $result['success'] ? Response::HTTP_OK : Response::HTTP_PARTIAL_CONTENT;

			return $this->json( $result, $status );
		} catch ( \Throwable $e ) {
			return $this->json(
				[
					'success' => false,
					'message' => $e->getMessage(),
					'type'    => get_class( $e ),
					'code'    => $e->getCode(),
					'file'    => $e->getFile(),
					'line'    => $e->getLine(),
					'trace'   => $e->getTraceAsString(),
				],
				Response::HTTP_INTERNAL_SERVER_ERROR
			);
		}
	}

	protected function getPreviewSchema( string $type = '' ): array
	{
		$fields = [
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

		return match ( $type ) {
			default => [
				'required' => [ 'config' ],
				'fields'   => $fields
			],
			'task' => [
				'type'        => 'task',
				'description' => 'Preview a single task configuration.',
				'required'    => [ 'config' ],
				'fields'      => $fields,
				'example'     => [
					'config' => [
						'_class' => 'Http',
						'url'    => 'https://api.example.com/data',
						'method' => 'GET',
					],
					'mode'   => 'safe',
				],
			],
			'routine' => [
				'type'        => 'routine',
				'description' => 'Preview a routine (sequence of tasks).',
				'required'    => [ 'config' ],
				'fields'      => $fields,
				'example'     => [
					'config' => [
						'_ref'  => 'my_routine',
						'tasks' => [
							[
								'_class' => 'Http',
								'url'    => 'https://api.example.com/data',
							],
						],
					],
					'mode'   => 'safe',
				],
			],
			'step' => [
				'type'        => 'step',
				'description' => 'Preview a single flow step.',
				'required'    => [ 'config' ],
				'fields'      => $fields,
				'example'     => [
					'config' => [
						'_ref' => 'my_step',
						'routine' => [
							'_ref' => 'my_routine',
							'input' => [],
							'variables' => [],
						],
					],
					'mode'   => 'safe',
				],
			],
			'flow' => [
				'type'        => 'flow',
				'description' => 'Preview a flow (sequence of steps).',
				'required'    => [ 'config' ],
				'fields'      => $fields,
				'example'     => [
					'config' => [
						'_ref'  => 'my_flow',
						'steps' => [
							[
								'_ref'    => 'step_1',
								'routine' => [ '_ref' => 'my_routine' ],
							],
						],
					],
					'mode'   => 'safe',
				],
			],
			'automation' => [
				'type'        => 'automation',
				'description' => 'Preview a full automation (source + actions).',
				'required'    => [ 'config' ],
				'fields'      => $fields,
				'example'     => [
					'config' => [
						'source'  => 'request',
						'request' => [],
						'actions' => [],
					],
					'mode'   => 'safe',
				],
			],
		};
	}
}
