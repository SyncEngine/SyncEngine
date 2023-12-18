<?php

namespace SyncEngine\Controller;

use SyncEngine\Entity\Automation;
use SyncEngine\Entity\Connection;
use SyncEngine\Entity\Dataset;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\DatasetModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecutePreview;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\Tasks;
use SyncEngine\Service\Webservices;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends DefaultController
{
	#[Route( '/api', name: 'api' )]
	public function index(): Response
	{
		$results = [ 'API status' => 'Online' ];

		return $this->json( $results );
	}

	#[Route( '/api/v1/{entity}/{id}', name: 'api_list', requirements: [ 'id' => '\d+' ] )]
	public function automation( Request $request, string $entity, int $id = 0 ): JsonResponse
	{
		return new JsonResponse();
	}

	#[Route( '/api/{endpoint}', name: 'api_endpoint' )]
	public function endpoint( Automation $automation, Execute $execute, Request $request = null ): Response
	{
		$model   = AutomationModel::get( $automation );
		$context = new ExecutionContext( $execute, $model );

		if ( $model->isRunning() ) {
			$context->addError( $this->trans( 'Automation is already running.' ) );
			return $this->json( [
				'success' => true,
				'errors' => $context->getErrors(),
			] );
		}

		$results = $execute->execute( $model, $context, $request );

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
