<?php

namespace App\Controller;

use App\Entity\Automation;
use App\Entity\StepOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\StepOrderController;

class ApiController extends AbstractController
{
	#[Route('/api/{endpoint}', name: 'api')]
	public function index(Automation $automation, Security $security, Request $request, EntityManagerInterface $entityManager): Response
	{
		$user = $security->getUser();
		if ($request->isMethod('POST')) {
			$datafields = json_decode($request->get('datafields'));
			if(!$automation->getFlow()){return $this->json(["Relation automation flow" => "Missing"]);}
			$StepOrderController = new StepOrderController();
			$stepOrders = $entityManager->getRepository(StepOrder::class)->findBy(["flow"=>$automation->getFlow()]);

			$done = $StepOrderController->ExecuteStepOrders($stepOrders, $datafields);

			$results = $done;



		} else {
			$results = ["API status" => "Online"];
		}
		return $this->json($results);
	}
}
