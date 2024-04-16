<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends DefaultController
{
	#[Route( '/', name: 'app_index' )]
	public function index(): Response
	{
		return $this->render('index.html.twig');
	}

	#[Route( '/design', name: 'app_design' )]
	public function design(): Response
	{
		return $this->render( '_dev/design.html.twig' );
	}
}
