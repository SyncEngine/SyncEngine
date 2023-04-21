<?php

namespace App\Controller;

use App\Entity\Flow;
use App\Form\FlowFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FlowController extends AbstractController
{
	#[Route('/flow/new', name: 'new_flow')]
	public function newAutomation(Request $request, EntityManagerInterface $entityManager): Response
	{
		$flow = new Flow();

		$form = $this->createForm(FlowFormType::class, $flow);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($flow);
			$entityManager->flush();
			$this->addFlash('success', 'Succesfully created!');

			return $this->redirectToRoute('app_index');
		}


		return $this->render('flow/new.html.twig', [
			'form' => $form->createView(),
		]);
	}
}
