<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\TraceModel;

class AdminController extends DefaultController
{
	#[Route('/', name: 'admin_index')]
	public function dashboard(): Response
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
}
