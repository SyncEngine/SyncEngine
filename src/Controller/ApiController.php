<?php

namespace App\Controller;

use App\Entity\Automation;
use App\Entity\Connection;
use App\Entity\Dataset;
use App\Model\AutomationModel;
use App\Model\ConnectionModel;
use App\Model\DatasetModel;
use App\Service\Execute;
use App\Service\ExecutePreview;
use App\Service\ExecutionContext;
use App\Service\Tasks;
use App\Service\Webservices;
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
			$model = new AutomationModel( new Automation() );
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
			$model = new ConnectionModel( new Connection() );
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
			$model = new DatasetModel( new Dataset() );
		}

		return $model->handleRequest( $request );
	}

	#[Route( '/api/{endpoint}', name: 'api_endpoint' )]
	public function endpoint( Automation $automation, Execute $execute, Request $request = null ): Response
	{
		$model   = AutomationModel::get( $automation );
		$context = new ExecutionContext( $execute, $model );

		if ( $model->isRunning() ) {
			$context->addError( 'Automation is already running.' );
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
		$scope = $request->get( 'scope' );

		$context = new ExecutionContext( $executePreview );

		$results = $executePreview->preview( $scope, $context, $request );

		return $this->json( $results );
	}

	#[Route( '/json/tasks', name: 'json_tasks' )]
	public function getTasks(): JsonResponse
	{
		return $this->json( [
			'success' => true,
			'data'    => Tasks::getTasksNormalized(),
		] );
	}

	#[Route( '/json/webservices', name: 'json_webservices' )]
	public function getWebservices(): JsonResponse
	{
		return $this->json( [
			'success' => true,
			'data'    => Webservices::getWebservicesNormalized(),
		] );
	}

}
