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

		$html = '';

		if ( $manager->isEnabled() ) {
			$html .= '<a class="btn btn-warning" href="' . $this->generateUrl( 'system_disable_manager' ) . '">Disable</a>';
		} else {
			$html .= '<a class="btn btn-primary" href="' . $this->generateUrl( 'system_enable_manager' ) . '">Enable</a>';
		}

		return $this->render( 'admin/system/index.html.twig', [
			'backlink'    => $this->generateUrl( 'system_index' ),
			'header'      => $this->trans( 'Processes' ),
			'icon'        => 'terminal',
			'html'        => $html,
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

		$manager->enableManager();

		return $this->redirectToRoute( 'system_processes' );
	}

	#[Route('system/processes/disable', name: 'system_disable_manager' )]
	function handleDisable( Request $request, MessengerManager $manager ): Response
	{
		$this->denyAccessUnlessGranted('ROLE_ADMIN');

		$manager->disableManager();

		return $this->redirectToRoute( 'system_processes' );
	}
}
