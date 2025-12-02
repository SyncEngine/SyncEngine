<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Attribute\MenuItem;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Repository\MessengerMessageRepository;

class AdminController extends DefaultController
{
	#[Route( '/', name: 'admin_index' )]
	#[MenuItem( menu: 'main', route: 'syncengine_admin_index', label: 'Dashboard', parent: 'dashboards', icon: 'dashboard', position: 0 )]
	public function dashboard( MessengerMessageRepository $messageRepository ): Response
	{
		$query = [
			'limit'        => 10,
			'total'        => true,
			'dependencies' => true,
			'order'        => [ 'created' => 'DESC' ],
		];

		$messengerItems = $messageRepository->findAll();
		$totalMessenger = count( $messengerItems );

		foreach ( $messengerItems as &$item ) {
			$decoded      = json_decode( $item['body'], true );
			$item['body'] = $decoded ?: substr( $item['body'], 0, 200 ) . '...';
			if ( ! empty( $item['body']['automationId'] ) ) {
				$item['automation'] = AutomationModel::get( $item['body']['automationId'] )?->normalize( false, false );
			}
		}

		return $this->render(
			'admin/dashboard.html.twig',
			[
				'traces'    => [
					'query' => $query,
					'items' => [],
					'total' => TraceModel::getTotalCount( $query ),
				],
				'messenger' => [
					'items' => $messengerItems,
					'total' => $totalMessenger,
					'query' => [
						'limit' => 10,
						'order' => [ 'created_at' => 'DESC' ],
					],
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
