<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use SyncEngine\Model\TraceModel;

class IndexController extends DefaultController
{
	#[Route( '/', name: 'app_index' )]
	public function index(): Response
	{
		$query = [
			'limit' => 10,
			'total' => true,
			'dependencies' => true,
			'order' => [ 'created' => 'DESC' ],
		];

		return $this->render( 'admin/dashboard.html.twig', [
			'traces' => [
				'query'   => $query,
				'items'   => [],
				'total'   => TraceModel::getTotalCount( $query ),
			],
		] );
	}

	#[Route( '/design', name: 'app_design' )]
	public function design(): Response
	{
		return $this->render( '_dev/design.html.twig' );
	}
}
