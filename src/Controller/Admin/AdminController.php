<?php

namespace SyncEngine\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Attribute\MenuItem;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\TraceModel;

class AdminController extends DefaultController
{
	#[Route( '/', name: 'admin_index' )]
	#[MenuItem( menu: 'main', route: 'syncengine_admin_index', label: 'Dashboard', parent: 'dashboards', icon: 'dashboard', position: 0 )]
	public function dashboard(
		#[Autowire( '%env(string:DB_TABLE_PREFIX)%' )]
		$prefix,
		EntityManagerInterface $em,
	): Response
	{
		$query = [
			'limit'        => 10,
			'total'        => true,
			'dependencies' => true,
			'order'        => [ 'created' => 'DESC' ],
		];

		$conn = $em->getConnection();
		$sql  = 'SELECT * FROM ' . $prefix . 'messenger_messages ORDER BY created_at DESC LIMIT 10';

		$messengerItems = $conn->executeQuery($sql)->fetchAllAssociative();
		foreach ( $messengerItems as &$item ) {
			$decoded = json_decode( $item['body'], true );
			$item['body'] = $decoded ?: substr( $item['body'], 0, 200 ) . '...';
			if ( ! empty( $item['body']['automationId'] ) ) {
				$item['automation'] = AutomationModel::get( $item['body']['automationId'] )?->normalize( false, false );
			}
		}

		$totalMessenger = $conn->executeQuery( 'SELECT COUNT(*) FROM ' . $prefix . 'messenger_messages' )->fetchOne();

		return $this->render(
			'admin/dashboard.html.twig',
			[
				'traces' => [
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
