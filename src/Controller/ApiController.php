<?php

namespace App\Controller;

use App\Entity\Automation;
use App\Entity\StepOrder;
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
		if ($request->isMethod('POST') or $request->isMethod('GET')) {
			$datafields = json_decode($request->get('datafields'), true);
			if (!$automation->getFlow()) {
				return $this->json(["Relation automation flow" => "Missing"]);
			}
			$StepOrderController = new StepOrderController();
			$stepOrders = $entityManager->getRepository(StepOrder::class)->findBy(["flow" => $automation->getFlow()]);
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
		$type = $connection->getAuthType();
		switch ($type) {
			case 'Basic auth':
				$response = $this->basicAuthMethod($config, $results);
				break;
			case 'FTP':
				$response = $this->uploadToFTP($config, $results, false);
				break;
			case 'SFTP':
				$response = $this->uploadToFTP($config, $results, true);
				break;
			case 'None':
				$response = $this->noAuthMethod($config, $results);
				break;
			case 'Bearer Token':
				$response = $this->bearerToken($config, $results);
				break;
		}
		return $response;
	}

	public function noAuthMethod($config, $results)
	{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $config["url"],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $results,
			CURLOPT_HTTPHEADER => ["accept: application/json", "content-type: application/json"]
		]);
		return $this->executeCurl($curl);
	}

	public function basicAuthMethod($config, $results)
	{
		$curl = curl_init();
		$base64 = base64_encode($config["username"] . ":" . $config["password"]);
		//@todo select content-type accordingly to results format
		$header = ["Authorization: Basic " . $base64, "accept: application/json", "content-type: application/json"];
		curl_setopt_array($curl, [
			CURLOPT_URL => $config["url"],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $results,
			CURLOPT_HTTPHEADER => $header
		]);

		return $this->executeCurl($curl);
	}

	public function uploadToFTP($config, $results, $sftp)
	{
		$filename = strval($config['filename']);
		$localCSVfile = fopen($filename, 'w');
		fputcsv($localCSVfile, $results);
		fclose($localCSVfile);

		$localCSVfile = fopen($filename, 'r');
		$curl = curl_init();
		if ($sftp) {
			curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_SFTP);
			$baseProtocol = 'sftp://';
		} else {
			$baseProtocol = 'ftp://';
		}
		curl_setopt($curl, CURLOPT_URL, $baseProtocol.$config["username"] . ':' . $config["password"] . '@' . $config["url"] . '/' . $config["path"] . '/' . $filename);
		curl_setopt($curl, CURLOPT_UPLOAD, 1);
		curl_setopt($curl, CURLOPT_INFILE, $localCSVfile);
		curl_setopt($curl, CURLOPT_INFILESIZE, filesize($filename));
		$response = $this->executeCurl($curl);
		fclose($localCSVfile);
		unlink($filename);

		return $response;
	}

	public function bearerToken($config, $results)
	{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $config["url"],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $results,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer '.$config["Bearer Token"]
			)
		]);
		return $this->executeCurl($curl);
	}

	public function executeCurl($curl)
	{
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
			$response = "cURL Error #:" . $err;
		}
		return $response;
	}
}
