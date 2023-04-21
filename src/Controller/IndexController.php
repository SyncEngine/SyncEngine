<?php

namespace App\Controller;

use App\Entity\Connection;
use App\Entity\Automation;
use App\Entity\Flow;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends DefaultController
{
	#[Route('/', name: 'app_index')]
	public function index(EntityManagerInterface $entityManager): Response
	{
		$connections = $entityManager->getRepository(Connection::class)->findAll();
		$automations = $entityManager->getRepository(Automation::class)->findAll();
		$flows = $entityManager->getRepository(Flow::class)->findAll();
		return $this->render('index/index.html.twig', [
			'connections' => $connections,
			'automations' => $automations,
			'flows' => $flows,
		]);
	}
}
