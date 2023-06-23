<?php

namespace App\Controller;

use App\Component\ExecutionContext;
use App\Entity\Automation;
use App\Service\AutomationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcher;
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
}
