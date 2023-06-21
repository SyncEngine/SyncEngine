<?php

namespace App\Controller;

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
	public function endpoint( Automation $automation, Request $request, AutomationService $automationService ): Response
	{
		$results = $automationService->execute( $automation, $request );
		return $this->json( $results );
	}


	#[Route('/api/{endpoint}/profiler', name: 'api_endpoint_profiler')]
	public function endpoint_profiler( Automation $automation, Request $request, AutomationService $automationService ): Response
	{
		$results = $automationService->execute( $automation, $request );
		return $this->render( 'api/endpoint.html.twig', [ 'response' => $results ] );
	}
}
