<?php

namespace App\Controller;

use App\Entity\Automation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
	#[Route('/api/{endpoint}', name: 'api')]
	public function index(Automation $automation, Security $security, Request $request): Response
	{
		$user = $security->getUser();
		if ($request->isMethod('POST')) {
			$datafields = $request->get('datafields');
			$results = json_decode($datafields);

		} else {
			$results = ["API status" => "Online"];
		}
		return $this->json($results);
	}
}
