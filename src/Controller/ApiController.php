<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Entity\Automation;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;

class ApiController extends DefaultController
{
	#[Route( '/api/status', name: 'api', methods: [ 'GET' ] )]
	public function index(): JsonResponse
	{
		return $this->json( [ 'status' => 'online' ] );
	}

	#[Route( '/api/endpoints', name: 'api_list_endpoints', methods: [ 'GET' ] )]
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
					'link'        => $this->generateUrl( 'syncengine_api_endpoint', [ 'endpoint' => $automation->getEndpoint() ] ),
				];
			}
		} catch ( \Exception $e ) {
			return $this->json( [ 'message' => $this->trans( $e->getMessage() ) ], Response::HTTP_INTERNAL_SERVER_ERROR );
		}

		return $this->json( $endpoints );
	}

	#[Route( '/api/{endpoint}', name: 'api_endpoint', methods: [ 'GET', 'POST' ] )]
	public function endpoint( Automation $automation, Execute $execute, Request $request = null ): JsonResponse
	{
		$model   = AutomationModel::get( $automation );
		$context = new ExecuteContext( $execute, $model );

		if ( $model->isRunning() ) {
			return $this->json( [ 'message' => $this->trans( 'Automation is already running.' ) ], Response::HTTP_LOCKED );
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

	// @todo Allow in dev only.
	#[Route( '/profiler/api/{endpoint}', name: 'api_endpoint_profiler' )]
	public function endpoint_profiler( Automation $automation, Execute $execute, Request $request = null ): Response
	{
		$response = $this->endpoint( $automation, $execute, $request );

		try {
			$response = $this->render( 'api/endpoint.html.twig', [ 'response' => json_decode( $response->getContent(), true ) ] );
		} catch ( \Exception $e ) {}

		return $response;
	}

}
