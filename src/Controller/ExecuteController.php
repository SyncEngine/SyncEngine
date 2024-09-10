<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Entity\Automation;
use SyncEngine\Service\Execute;

class ExecuteController extends DefaultController
{
	// @todo Allow in dev only.
	#[Route( '/profiler/api/{endpoint}', name: 'endpoint_profiler' )]
	public function endpoint_profiler( Automation $automation, Execute $execute, Request $request = null ): Response
	{
		$response = $this->endpoint( $automation, $execute, $request );

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
