<?php

namespace App\Controller;

use App\Entity\Automation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends DefaultController
{
	#[Route('/', name: 'app_index')]
	public function index(EntityManagerInterface $entityManager): Response
	{
		$automations = $entityManager->getRepository(Automation::class)->findAll();
		return $this->render('index/index.html.twig', [
			'automations' => $automations,
		]);
	}
}
