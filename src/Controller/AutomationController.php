<?php

namespace App\Controller;

use App\Entity\Automation;
use App\Form\AutomationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AutomationController extends AbstractController
{
	#[Route('/automation/new', name: 'new_automation')]
	public function newAutomation(Request $request, EntityManagerInterface $entityManager): Response
	{
		$automation = new Automation();

		$form = $this->createForm(AutomationFormType::class, $automation);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$helper = new DefaultController();
			$slug = $helper->slugify($automation->getName());
			$automation->setSlug($slug);
			$entityManager->persist($automation);
			$entityManager->flush();
			$this->addFlash('success', 'Succesfully created!');

			return $this->redirectToRoute('app_index');
		}


		return $this->render('automation/new.html.twig', [
			'form' => $form->createView(),
		]);
	}
}
