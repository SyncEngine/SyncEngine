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
		if ($request->isMethod('POST') or $request->isMethod('GET')) {
			$datafields = json_decode($request->get('datafields'),true);
			if(!$automation->getFlow()){return $this->json(["Relation automation flow" => "Missing"]);}
			$StepOrderController = new StepOrderController();
			$stepOrders = $entityManager->getRepository(StepOrder::class)->findBy(["flow"=>$automation->getFlow()]);
			$results = $StepOrderController->ExecuteStepOrders($stepOrders, $datafields);
			$results = $this->sendResultsToTarget($automation->getTargetConnection(), $results);

		} else {
			$results = ["API status" => "Online"];
		}
		return $this->json($results);
	}

	public function sendResultsToTarget($connection, $results)
	{
		$config = $connection->getConfig();
		$completeJsonData = json_encode($results);

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => $config["url"],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $completeJsonData,
			CURLOPT_HTTPHEADER => $config["header"]
		]);

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {$response = "cURL Error #:" . $err;}
		return $response;

	}
}
