<?php

namespace App\Controller;

use App\Entity\Flow;
use App\Form\FlowFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FlowController extends DefaultController
{
	#[Route('/flow/create', name: 'create_flow')]
	public function create(Request $request, EntityManagerInterface $entityManager): Response
	{
		$flow = new Flow();

		$form = $this->createForm(FlowFormType::class, $flow);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($flow);
			$entityManager->flush();
			$this->addFlash('success', 'Succesfully added!');

			return $this->redirectToRoute('app_index');
		}

		return $this->render('flow/create.html.twig', [
			'form' => $form,
		]);
	}

	#[Route('/flow/edit/{id}', name: 'edit_flow')]
	public function edit(Flow $flow, Request $request, EntityManagerInterface $entityManager): Response
	{
		$form = $this->createForm(FlowFormType::class, $flow);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($flow);
			$entityManager->flush();
			$this->addFlash('success', 'Succesfully edited!');

			return $this->redirectToRoute('app_index');
		}

		return $this->render('flow/edit.html.twig', [
			'form' => $form,
		]);
	}
}
