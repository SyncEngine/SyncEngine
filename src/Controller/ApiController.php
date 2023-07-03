<?php

namespace App\Controller;

use App\Component\ExecutionContext;
use App\Entity\Automation;
use App\Entity\Connection;
use App\Entity\Dataset;
use App\Service\AutomationService;
use App\Service\ConnectionService;
use App\Service\DatasetService;
use App\Service\TaskService;
use App\Service\WebserviceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
	#[Route('/api', name: 'api')]
	public function index(): Response
	{
		$results = [ 'API status' => 'Online' ];
		return $this->json( $results );
	}

	#[Route('/api/{endpoint}', name: 'api_endpoint')]
	public function endpoint( Automation $automation, AutomationService $automationService, Request $request ): Response
	{
		$model = AutomationService::getAutomation( $automation );
		$context = new ExecutionContext( $model, $request );

		$results = $automationService->execute( $model, $context, [] );
		return $this->json( $results );
	}

	// @todo Allow in dev only.
	#[Route('/api/{endpoint}/profiler', name: 'api_endpoint_profiler')]
	public function endpoint_profiler( Automation $automation, AutomationService $automationService, Request $request ): Response
	{
		$model = AutomationService::getAutomation( $automation );
		$context = new ExecutionContext( $model, $request );

		$results = $automationService->execute( $model, $context, [] );
		return $this->render( 'api/endpoint.html.twig', [ 'response' => $results ] );
	}

	#[Route('/api/automation/{id}', name: 'api_automation')]
	public function automation( Automation $automation, Request $request ): Response
	{
		$model = AutomationService::getAutomation( $automation );
		$results = $model->handleRequest( $request );
		return $this->json( $results );
	}

	#[Route('/api/connection/{id}', name: 'api_connection')]
	public function connection( Connection $connection, Request $request ): Response
	{
		$model = ConnectionService::getConnection( $connection );
		$results = $model->handleRequest( $request );
		return $this->json( $results );
	}

	#[Route('/api/dataset/{id}', name: 'api_dataset')]
	public function dataset( Dataset $dataset, Request $request ): Response
	{
		$model = DatasetService::getDataset( $dataset );
		$results = $model->handleRequest( $request );
		return $this->json( $results );
	}

	public function getTasks(): Response
	{
		return $this->json( TaskService::getTasksNormalized() );
	}

	public function getWebservices(): Response
	{
		return $this->json( WebserviceService::getWebservicesNormalized() );
	}

}
