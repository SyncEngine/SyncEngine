<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebserviceController extends AbstractController
{
	public function apiKey($config, $results)
	{
		$curl = curl_init();

		curl_setopt_array($curl, array(
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
				$config["key"].': '.$config["value"]
			),
		));

		return $this->executeCurl($curl);
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
		curl_setopt($curl, CURLOPT_URL, $baseProtocol . $config["username"] . ':' . $config["password"] . '@' . $config["url"] . '/' . $config["path"] . '/' . $filename);
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
				'Authorization: Bearer ' . $config["Bearer Token"]
			)
		]);
		return $this->executeCurl($curl);
	}

	public function getFTP($connectionConfig,$config)
	{

		$curl = curl_init();
		$file = fopen($config['filename'], 'w');
		curl_setopt($curl, CURLOPT_URL, $connectionConfig["host"].$config["path"].$config['filename']); #input
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FILE, $file); #output
		curl_setopt($curl, CURLOPT_USERPWD, $connectionConfig['username'].":".$connectionConfig["password"]);
		$response = $this->executeCurl($curl);
		fclose($file);

		if(strval($response) and str_contains($response, "cURL Error #")) {
			return [$response];
		}

		return $file;
	}

	public function basicAuthMethod($connectionConfig, $config,$data)
	{

		$curl = curl_init();
		$base64 = base64_encode($connectionConfig["username"] . ":" . $connectionConfig["password"]);
		//@todo select content-type accordingly to results format and send the data

		curl_setopt_array($curl, array(
			CURLOPT_URL => $connectionConfig["host"],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => array(
				'Authorization: Basic '.$base64
			),
		));
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
