<?php

namespace App\Controller;

use App\Entity\Automation;
use App\Service\AutomationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
	#[Route('/api/{endpoint}', name: 'api')]
	public function index(Automation $automation, Request $request, EntityManagerInterface $entityManager, AutomationService $automationService): Response
	{
		if (!$automation->getFlow()) {
			return $this->json(["Relation automation flow" => "Missing"]);
		}

		if ($request->isMethod('POST')) {
			$datafields = json_decode($request->get('datafields'), true);
			$results = $automationService->execute( $automation, $datafields );
			//$results = $this->sendResultsToTarget($automation->getTargetConnection(), $results);
		} elseif ($request->isMethod('GET')) {
			$results = ["API status" => "Online"];
		}

		return $this->json($results);
	}
}
