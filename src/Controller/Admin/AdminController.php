<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Attribute\MenuItem;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\TraceModel;

class AdminController extends DefaultController
{
	#[Route( '/', name: 'admin_index' )]
	#[MenuItem( menu: 'main', route: 'syncengine_admin_index', label: 'Dashboard', parent: 'dashboards', icon: 'dashboard', position: 0 )]
	public function dashboard(): Response
	{
		$query = [
			'limit'        => 10,
			'total'        => true,
			'dependencies' => true,
			'order'        => [ 'created' => 'DESC' ],
		];

		return $this->render(
			'admin/dashboard.html.twig',
			[
				'traces' => [
					'query' => $query,
					'items' => [],
					'total' => TraceModel::getTotalCount( $query ),
				],
			]
		);
	}

	#[Route( '/heartbeat', name: 'admin_heartbeat' )]
	public function heartbeat(): Response
	{
		return new Response();
	}
}
