<?php

namespace SyncEngine\Controller;

use SyncEngine\Entity\Automation;
use SyncEngine\Entity\Connection;
use SyncEngine\Entity\Flow;
use SyncEngine\Entity\Step;
use SyncEngine\Entity\Dataset;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Env;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends DefaultController
{
	#[Route( '/', name: 'app_index' )]
	public function index(): Response
	{
		$query = [
			'limit' => 10,
			'total' => true,
			'order' => [ 'created' => 'DESC' ],
		];

		$traces = TraceModel::getAll( $query );

		$items = array_map( function ( $item ) { return $item->normalize( true ); }, $traces );

		return $this->render( 'admin/dashboard.html.twig', [
			'traces' => [
				'query'   => $query,
				'items'   => $items,
				'total'   => TraceModel::getTotalCount( $query ),
				'columns' => [
					'info'       => [
						'label' => 'Date',
						'prop'  => 'created',
						'parse' => 'date',
					],
					'Status'     => [
						'block' => 'info',
						'prop'  => 'status',
					],
					'automation' => [
						'block' => 'entities',
						'prop'  => 'automation',
						'multi' => false,
					],
					'actions'    => [
						'label'   => 'View',
						'actions' => [
							'view' => [
								'variant' => 'info',
								'confirm' => false,
								'action'  => 'request',
								'request' => 'view',
								'label'   => 'View',
								'title'   => 'Trace {{ created }}: {{ automation.name }} ({{ status }})',
							],
						],
					],
				],
			],
		] );
	}

	#[Route( '/design', name: 'app_design' )]
	public function design(): Response
	{
		return $this->render( '_dev/design.html.twig' );
	}
}
