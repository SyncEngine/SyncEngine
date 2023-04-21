<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends DefaultController
{
	#[Route('/admin', name: 'app_admin')]
	public function index(): Response
	{
		return $this->render('admin/index.html.twig', [
			'controller_name' => 'AdminController',
		]);
	}
}
