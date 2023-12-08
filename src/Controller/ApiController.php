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

	#[Route( '/api/automation/{id}', name: 'api_automation', requirements: [ 'id' => '\d+' ] )]
	public function automation( Request $request, int $id = 0 ): Response
	{
		if ( ! $id ) {
			$id = $request->request->get( 'id' );
		}
		if ( $id ) {
			$model = AutomationModel::get( $id );
		} else {
			$model = AutomationModel::create();
		}

		return $model->handleRequest( $request );
	}

	#[Route( '/api/connection/{id}', name: 'api_connection', requirements: [ 'id' => '\d+' ] )]
	public function connection( Request $request, int $id = 0 ): Response
	{
		if ( ! $id ) {
			$id = $request->request->get( 'id' );
		}
		if ( $id ) {
			$model = ConnectionModel::get( $id );
		} else {
			$model = ConnectionModel::create();
		}

		return $model->handleRequest( $request );
	}

	#[Route( '/api/dataset/{id}', name: 'api_dataset', requirements: [ 'id' => '\d+' ] )]
	public function dataset( Request $request, int $id = 0 ): Response
	{
		if ( ! $id ) {
			$id = $request->request->get( 'id' );
		}
		if ( $id ) {
			$model = DatasetModel::get( $id );
		} else {
			$model = DatasetModel::create();
		}

		return $model->handleRequest( $request );
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

	#[Route( '/json/preview', name: 'json_preview', requirements: [] )]
	public function preview( ExecutePreview $executePreview, Request $request = null ): Response
	{
		return $this->json( $executePreview->preview( $request ) );
	}

	#[Route( '/json/tasks', name: 'json_tasks' )]
	public function getTasks( Tasks $tasksService ): JsonResponse
	{
		return $this->json( [
			'success' => true,
			'data'    => $tasksService->getTasksNormalized(),
		] );
	}

	#[Route( '/json/webservices', name: 'json_webservices' )]
	public function getWebservices( Webservices $webservicesService ): JsonResponse
	{
		return $this->json( [
			'success' => true,
			'data'    => $webservicesService->getWebservicesNormalized(),
		] );
	}

}
