<?php

namespace App\Controller;

use App\Entity\Automation;
use App\Form\AutomationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AutomationController extends DefaultController
{
	#[Route('/automation/create', name: 'create_automation')]
	public function create(Request $request, EntityManagerInterface $entityManager): Response
	{
		$automation = new Automation();

		$form = $this->createForm(AutomationFormType::class, $automation);

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$endpoint = $this->slugify($automation->getEndpoint());
			$automation->setEndpoint($endpoint);
			$entityManager->persist($automation);
			$entityManager->flush();
			$this->addFlash('success', 'Succesfully created!');

			return $this->redirectToRoute('app_index');
		}

		return $this->render('automation/create.html.twig', [
			'form' => $form,
		]);
	}

	#[Route('/automation/edit/{id}', name: 'edit_automation')]
	public function newAutomation(Automation $automation, Request $request, EntityManagerInterface $entityManager): Response
	{
		$form = $this->createForm(AutomationFormType::class, $automation);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$endpoint = $this->slugify($automation->getEndpoint());
			$automation->setEndpoint($endpoint);
			$entityManager->persist($automation);
			$entityManager->flush();
			$this->addFlash('success', 'Succesfully edited!');

			return $this->redirectToRoute('app_index');
		}

		return $this->render('automation/edit.html.twig', [
			'form' => $form,
		]);
	}
}
