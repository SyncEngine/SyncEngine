<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Entity\Automation;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecutionContext;

class ApiController extends DefaultController
{
	#[Route( '/api/status', name: 'api' )]
	public function index(): JsonResponse
	{
		$results = [ $this->trans( 'API status' ) => $this->trans( 'Online' ) ];

		return $this->json( $results );
	}

	#[Route( '/api/v1/{entity}/{id}', name: 'api_list', requirements: [ 'id' => '\d+' ] )]
	public function list_entities( Request $request, string $entity, int $id = 0 ): JsonResponse
	{
		return $this->index();
	}

	#[Route( '/api/{endpoint}', name: 'api_endpoint' )]
	public function endpoint( Automation $automation, Execute $execute, Request $request = null ): JsonResponse
	{
		$model   = AutomationModel::get( $automation );
		$context = new ExecutionContext( $execute, $model );

		if ( $model->isRunning() ) {
			$context->addError( $this->trans( 'Automation is already running.' ) );

			return $this->json(
				[
					'success' => true,
					'errors'  => $context->getErrors(),
				]
			);
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
		$results = $this->endpoint( $automation, $execute, $request )->getContent();

		return $this->render( 'api/endpoint.html.twig', [ 'response' => json_decode( $results, true ) ] );
	}

}
