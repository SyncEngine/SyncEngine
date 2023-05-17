<?php

namespace App\Controller;

use App\Entity\Connection;
use App\Form\ConnectionFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConnectionController extends DefaultController
{
	#[Route('/connection/create', name: 'create_connection')]
	public function create( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$connection = new Connection();

		$form = $this->createForm( ConnectionFormType::class, $connection );
		$form->add( 'save', SubmitType::class, ['label' => 'Create'] );

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist( $connection );
			$entityManager->flush();

			$this->addFlash('success', 'Successfully created connection!');

			return $this->redirectToRoute('app_index');
		}


		return $this->render('connection/create.html.twig', [
			'form' => $form,
		]);
	}

	#[Route('/connection/edit/{id}', name: 'edit_connection')]
	public function edit( Connection $connection, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->createForm(ConnectionFormType::class, $connection );
		$form->add( 'save', SubmitType::class, ['label' => 'Update'] );

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist( $connection );
			$entityManager->flush();

			$this->addFlash( 'success', 'Successfully created connection!' );

			return $this->redirectToRoute('app_index');
		}


		return $this->render('connection/edit.html.twig', [
			'form' => $form,
		]);
	}
}
