<?php

namespace SyncEngine\Controller;

use SyncEngine\Entity\Automation;
use SyncEngine\Entity\Connection;
use SyncEngine\Entity\Flow;
use SyncEngine\Entity\Step;
use SyncEngine\Entity\Dataset;
use SyncEngine\Service\Env;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends DefaultController
{
	#[Route( '/', name: 'app_index' )]
	public function index( EntityManagerInterface $entityManager ): Response
	{
		$connections = $entityManager->getRepository( Connection::class )->findAll();
		$automations = $entityManager->getRepository( Automation::class )->findAll();
		$flows       = $entityManager->getRepository( Flow::class )->findAll();
		$steps       = $entityManager->getRepository( Step::class )->findAll();
		$datasets    = $entityManager->getRepository( Dataset::class )->findAll();

		return $this->render( 'admin/dashboard.html.twig', [
			'connections' => $connections,
			'automations' => $automations,
			'flows'       => $flows,
			'steps'       => $steps,
			'datasets'    => $datasets,
		] );
	}

	#[Route( '/design', name: 'app_design' )]
	public function design(): Response
	{
		return $this->render( '_dev/design.html.twig' );
	}
}
