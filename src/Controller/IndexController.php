<?php

namespace App\Controller;

use App\Entity\Automation;
use App\Entity\Connection;
use App\Entity\Flow;
use App\Entity\Step;
use App\Entity\Dataset;
use App\Service\Env;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends DefaultController
{
	#[Route('/', name: 'app_index')]
	public function index(EntityManagerInterface $entityManager, Env $env ): Response
	{
		if ( ! $env->get( 'DATABASE_URL' ) ) {
			$this->redirectToRoute( 'install_index' );
		}

		$connections = $entityManager->getRepository(Connection::class)->findAll();
		$automations = $entityManager->getRepository(Automation::class)->findAll();
		$flows = $entityManager->getRepository(Flow::class)->findAll();
		$steps = $entityManager->getRepository(Step::class)->findAll();
		$datasets = $entityManager->getRepository(Dataset::class)->findAll();

		return $this->render('admin/dashboard.html.twig', [
			'connections' => $connections,
			'automations' => $automations,
			'flows' => $flows,
			'steps' => $steps,
			'datasets' => $datasets,
		]);
	}
}
