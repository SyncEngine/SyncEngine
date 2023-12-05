<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class MenuController extends DefaultController
{
	public function jsonMenu(): JsonResponse
	{
		return $this->json( [] );
	}
}
