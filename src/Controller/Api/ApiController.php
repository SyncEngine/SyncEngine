<?php

namespace SyncEngine\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\DefaultController;

class ApiController extends DefaultController
{
	#[Route( '/status', name: 'status', methods: [ 'GET' ] )]
	public function index(): JsonResponse
	{
		return $this->json( [ 'status' => 'online' ] );
	}

}
