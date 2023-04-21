<?php

namespace App\Controller;

use App\Entity\Connection;
use App\Form\ConnectionFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ConnectionController extends AbstractController
{
	#[Route('/connection/new', name: 'new_connection')]
	public function newConnection(Request $request, EntityManagerInterface $entityManager): Response
	{
		$connection = new Connection();

		$form = $this->createForm(ConnectionFormType::class, $connection);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($connection);
			$entityManager->flush();

			$this->addFlash('success', 'Succesfully created!');
			return $this->redirectToRoute('app_index');
		}


		return $this->render('connection/new.html.twig', [
			'form' => $form->createView(),
		]);
	}
}
