<?php

namespace App\Controller;

use App\Entity\Connection;
use App\Form\ConnectionFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConnectionController extends DefaultController
{
	#[Route('/connection/new', name: 'new_connection')]
	public function newConnection(Request $request, EntityManagerInterface $entityManager): Response
	{
		$connection = new Connection();

		// @todo second $connection param.
		//$form = $this->createForm(ConnectionFormType::class); //$connection

		$form = $this->createFormBuilder()
			->add('name', TextType::class)
			->add('description', TextType::class)
			->add( 'config', TextareaType::class )
			->getForm();

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$frm = $form->getData();

			$connection->setName($frm['name']);
			$connection->setDescription($frm['description']);
			$connection->setConfig(explode(',',$frm['config']));

			$entityManager->persist($connection);
			$entityManager->flush();

			$this->addFlash('success', 'Succesfully created!');
			return $this->redirectToRoute('app_index');
		}


		return $this->render('connection/new.html.twig', [
			'form' => $form,
		]);
	}
}
