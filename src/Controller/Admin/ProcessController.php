<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Messenger\MessengerManager;

class ProcessController extends DefaultController
{
	#[Route('system/processes', name: 'system_processes' )]
	function renderIndex( Request $request, MessengerManager $manager ): Response
	{
		$this->denyAccessUnlessGranted('ROLE_ADMIN');

		$card = [
			'icon'   => 'cpu',
			'header' => $this->trans( 'Manager' ),
			'body'   => 'Auto-starts background async workers to handle automations.',
		];

		if ( ! $manager->isInternal() ) {
			$card['badge'] = [
				'text'    => 'External',
				'variant' => 'info',
			];
		} elseif ( $manager->isEnabled() ) {
			$card['link'] = [
				'url'     => $this->generateUrl( 'system_disable_manager' ),
				'text'    => $this->trans( 'Disable' ),
				'variant' => 'outline-warning',
			];
			$card['badge'] = [
				'text'    => $this->trans( 'Enabled' ),
				'variant' => 'success',
			];
		} else {
			$card['link'] = [
				'url'     => $this->generateUrl( 'system_enable_manager' ),
				'text'    => $this->trans( 'Enable' ),
				'variant' => 'outline-success',
			];
			$card['badge'] = [
				'text'    => $this->trans( 'Disabled' ),
				'variant' => 'warning',
			];
		}

		return $this->render( 'admin/system/index.html.twig', [
			'backlink'    => $this->generateUrl( 'system_index' ),
			'header'      => $this->trans( 'Processes' ),
			'icon'        => 'terminal',
			'cards'       => [
				'manager' => $card,
			],
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'system_index' ),
					'title' => $this->trans( 'System' ),
				],
				[
					'title'   => $this->trans( 'Processes' ),
					'current' => true,
				],
			],
		] );
	}

	#[Route('system/processes/enable', name: 'system_enable_manager' )]
	function handleEnable( Request $request, MessengerManager $manager ): Response
	{
		$this->denyAccessUnlessGranted('ROLE_ADMIN');

		$manager->enable();

		return $this->redirectToRoute( 'system_processes' );
	}

	#[Route('system/processes/disable', name: 'system_disable_manager' )]
	function handleDisable( Request $request, MessengerManager $manager ): Response
	{
		$this->denyAccessUnlessGranted('ROLE_ADMIN');

		$manager->disable();

		return $this->redirectToRoute( 'system_processes' );
	}
}
