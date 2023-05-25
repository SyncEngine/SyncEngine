<?php

namespace App\Controller;

use App\Entity\Automation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
	#[Route('/api/{endpoint}', name: 'api')]
	public function index(Automation $automation, Request $request, EntityManagerInterface $entityManager): Response
	{
		if (!$automation->getFlow()) {
			return $this->json(["Relation automation flow" => "Missing"]);
		}
		$flowController = new FlowController();

		if ($request->isMethod('POST')) {
			$datafields = json_decode($request->get('datafields'), true);
			$results = $flowController->executeFlow($entityManager, $automation->getFlow(), $datafields);
			//$results = $this->sendResultsToTarget($automation->getTargetConnection(), $results);

		}elseif ($request->isMethod('GET'))
		{

		}
		else {
			$results = ["API status" => "Online"];
		}



		return $this->json($results);
	}

	public function sendResultsToTarget($connection, $results)
	{
		$config = $connection->getConfig();
		$webserviceController = new WebserviceController();
		$response = $results;
		switch ($config['auth_type']) {
			case 'Basic auth':
				$response = $webserviceController->basicAuthMethod($config, $results);
				break;
			case 'FTP':
				$response = $webserviceController->uploadToFTP($config, $results, false);
				break;
			case 'SFTP':
				$response = $webserviceController->uploadToFTP($config, $results, true);
				break;
			case 'None':
				$response = $webserviceController->noAuthMethod($config, $results);
				break;
			case 'Bearer Token':
				$response = $webserviceController->bearerToken($config, $results);
				break;
			case 'API Key':
				$response = $webserviceController->apiKey($config, $results);
				break;
		}
		return $response;
	}
}
