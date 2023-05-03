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

class ApiController extends AbstractController
{
	#[Route('/api/{endpoint}', name: 'api')]
	public function index(Automation $automation, Security $security, Request $request, EntityManagerInterface $entityManager): Response
	{
		$user = $security->getUser();
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
		if ($connection->getAuthType() === "Basic auth") {
			$response = $this->basicAuthMethod($config, $results);
		}
		if ($connection->getAuthType() === "FTP") {
			$response = $this->uploadToFTP($config, $results);
		}
		if (!$connection->getAuthType()) {
			$response = $this->noAuthMethod($config, $results);
		}
		return $response;
	}

	public function noAuthMethod($config, $results)
	{
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
			CURLOPT_HTTPHEADER => ["accept: application/json", "content-type: application/json"]
		]);
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
			$response = "cURL Error #:" . $err;
		}
		return $response;
	}

	public function basicAuthMethod($config, $results)
	{
		$completeJsonData = json_encode($results);
		$curl = curl_init();
		$base64 = base64_encode($config["username"] . ":" . $config["password"]);
		$header = ["Authorization: Basic " . $base64, "accept: application/json", "content-type: application/json"];
		curl_setopt_array($curl, [
			CURLOPT_URL => $config["url"],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $completeJsonData,
			CURLOPT_HTTPHEADER => $header
		]);
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
			$response = "cURL Error #:" . $err;
		}
		return $response;
	}

	public function uploadToFTP($config, $results)
	{
		$filename = strval( $config['filename'] );

		$localCSVfile = fopen($filename, 'w');

		switch( $config['filytype'] ) {
			case 'csv':
				fputcsv($localCSVfile, $results);
			break;
			case 'json':
			break;
		}

		fclose($localCSVfile);

		$localCSVfile = fopen($filename, 'r');

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'ftp://' . $config["username"] . ':' . $config["password"] . '@' . $config["url"] . '/' . $config["path"] . '/' . $filename);
		curl_setopt($curl, CURLOPT_UPLOAD, 1);
		curl_setopt($curl, CURLOPT_INFILE, $localCSVfile);
		curl_setopt($curl, CURLOPT_INFILESIZE, filesize($filename));
		curl_exec($curl);
		curl_close($curl);

		fclose($localCSVfile);
		unlink($filename);

		$error_no = curl_errno($curl);
		if ($error_no == 0) {
			$response = 'File uploaded succesfully.';
		} else {
			$response = 'File upload error.';
		}
		return $response;
	}
}
