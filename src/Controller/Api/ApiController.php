<?php

namespace SyncEngine\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\DefaultController;

class ApiController extends DefaultController
{
	#[Route( '/', name: 'index' )]
	public function index(): JsonResponse
	{
		return new JsonResponse( [] );
	}

	#[Route( '/status', name: 'status', methods: [ 'GET' ] )]
	public function status(): JsonResponse
	{
		return $this->json( [ 'status' => 'online' ] );
	}

}
