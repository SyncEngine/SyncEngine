<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\Api\ApiEndpointController;
use SyncEngine\Service\Execute;

class ExecuteController extends DefaultController
{
	// @todo Allow in dev only.
	#[Route( '/execute/endpoint/{endpoint}', name: 'endpoint_execute' )]
	public function endpoint_profiler( string $endpoint, ApiEndpointController $endpointController, Execute $execute, Request $request = null ): Response
	{
		$response = $endpointController->endpoint( $endpoint, $execute, $request );

		$results = json_decode( $response->getContent(), true );
		if ( isset( $results['success'] ) ) {
			$results['code'] = $response->getStatusCode();
		}

		try {
			$response = $this->render( 'api/endpoint.html.twig', [ 'response' => $results ] );
		} catch ( \Exception $e ) {}

		return $response;
	}
}
