<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\Api\ApiEndpointController;
use SyncEngine\Service\Execute;
use SyncEngine\Service\System;

class ExecuteController extends DefaultController
{
	// @todo Allow in dev only?
	#[Route( '/execute/endpoint/{endpoint:endpoint}/{action:action}', name: 'endpoint_execute', defaults: [ 'action' => 'execute' ] )]
	public function endpoint_profiler( string $endpoint, string $action, ApiEndpointController $endpointController, Execute $execute, ?Request $request = null ): Response
	{
		$response = $endpointController->endpoint( $endpoint, $action, $execute, $request );

		$results = json_decode( $response->getContent(), true );
		if ( isset( $results['success'] ) ) {
			$results['code'] = $response->getStatusCode();
		}

		try {
			$response = $this->render( 'api/endpoint.html.twig', [ 'response' => $results ] );
		} catch ( \Exception $e ) {}

		return $response;
	}

	#[Route( '/execute/clear:cache', name: 'execute_clear_cache' )]
	public function execute_clear_cache( System $system, ?Request $request = null ): Response
	{
		$redirect = $request->request->get( 'redirect' );

		$system->runCommand( 'cache:clear' );

		if ( $redirect ) {
			return $this->redirect( $redirect );
		}

		return $this->redirectToRoute( 'syncengine_system_index' );
	}
}
