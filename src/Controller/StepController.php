<?php

namespace App\Controller;

use App\Entity\Step;
use App\Form\StepFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StepController extends AbstractController
{
	#[Route('/step/create', name: 'create_step')]
	public function create( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$step = new Step();

		$form = $this->createForm( StepFormType::class, $step );
		$form->add( 'save', SubmitType::class, ['label' => 'Create'] );

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist( $step );
			$entityManager->flush();

			$this->addFlash('success', 'Successfully created step!');

			return $this->redirectToRoute('app_index');
		}

		return $this->render('step/create.html.twig', [
			'form' => $form,
		]);
	}

	#[Route('/step/edit/{id}', name: 'edit_step')]
	public function edit(Step $step, Request $request, EntityManagerInterface $entityManager): Response
	{
		$form = $this->createForm(StepFormType::class, $step);
		$form->add('save', SubmitType::class, ['label' => 'Update']);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {

			$entityManager->persist($step);
			$entityManager->flush();

			$this->addFlash('success', 'Successfully edited step!');

			return $this->redirectToRoute('app_index');
		}

		return $this->render('step/edit.html.twig', [
			'form' => $form,
		]);
	}
}
