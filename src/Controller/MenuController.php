<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class MenuController extends AbstractController
{
	public function jsonMenu(): JsonResponse
	{
		return $this->json( [] );
	}
}
