<?php

namespace App\Controller;

use App\Entity\Dataset;
use App\Form\DatasetFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DatasetController extends DefaultController
{
	#[Route('/dataset/create', name: 'create_dataset')]
	public function create( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$dataset = new Dataset();

		$form = $this->createForm( DatasetFormType::class, $dataset );
		$form->add( 'save', SubmitType::class, ['label' => 'Create'] );

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist( $dataset );
			$entityManager->flush();

			$this->addFlash('success', 'Successfully created dataset!');

			return $this->redirectToRoute('app_index');
		}


		return $this->render('dataset/create.html.twig', [
			'form' => $form,
		]);
	}

	#[Route('/dataset/edit/{id}', name: 'edit_dataset')]
	public function edit( Dataset $dataset, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->createForm(DatasetFormType::class, $dataset );
		$form->add( 'save', SubmitType::class, ['label' => 'Update'] );

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist( $dataset );
			$entityManager->flush();

			$this->addFlash( 'success', 'Successfully created dataset!' );

			return $this->redirectToRoute('app_index');
		}


		return $this->render('dataset/edit.html.twig', [
			'form' => $form,
		]);
	}
}
