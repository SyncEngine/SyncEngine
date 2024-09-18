<?php

namespace SyncEngine\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;

class ApiEndpointController extends ApiController
{
	#[Route( '/endpoint', name: 'list_endpoints', methods: [ 'GET' ] )]
	public function list_endpoints( Request $request ): JsonResponse
	{
		try {
			$query = $request->request->all();

			// Max 100 items.
			if ( ! isset( $query['limit'] ) || 100 < $query['limit'] ) {
				$query['limit'] = 100;
			}

			$automations = AutomationModel::getAll( $query );

			$endpoints = [];
			foreach ( $automations as $automation ) {
				$endpoints[] = [
					'endpoint'    => (string) $automation->getEndpoint(),
					'name'        => (string) $automation->getName(),
					'description' => (string) $automation->getDescription(),
					'link'        => $this->generateUrl(
						'syncengine_api_endpoint_execute',
						[ 'endpoint' => $automation->getEndpoint() ],
						UrlGeneratorInterface::ABSOLUTE_URL
					),
				];
			}
		} catch ( \Exception $e ) {
			return $this->json( [ 'message' => $this->trans( $e->getMessage() ) ], Response::HTTP_INTERNAL_SERVER_ERROR );
		}

		return $this->json( $endpoints );
	}

	#[Route( '/endpoint/{endpoint}', name: 'endpoint_execute', methods: [ 'GET', 'POST' ] )]
	public function endpoint( string $endpoint, Execute $execute, Request $request = null ): JsonResponse
	{
		$model = AutomationModel::get( [ 'endpoint' => $endpoint ] );

		if ( ! $model || ! $model->hasEntity() ) {
			return $this->json( [ 'message' => $this->trans( 'Endpoint not found: {value}', [ 'value' => $endpoint ] ) ], Response::HTTP_NOT_FOUND );
		}

		$context = new ExecuteContext( $execute, $model );

		if ( $model->isRunning() ) {
			return $this->json( [ 'message' => $this->trans( 'Automation is already running' ) ], Response::HTTP_LOCKED );
		}

		$context->setRequest( $request );
		$results = $execute->execute( $model, $context, $request );

		switch ( $model->getConfig( 'response' ) ) {
			case 'success':
				$results = $results['success'];
			break;
			case 'data':
				$results = $results['data'];
			break;
		}

		return $this->json( $results );
	}

}
