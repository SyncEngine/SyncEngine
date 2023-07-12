<?php

namespace App\Controller;

use App\Entity\Connection;
use App\Form\ConnectionFormType;
use App\Service\ConnectionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConnectionController extends EntityController
{
	#[Route( '/connection/json', 'json_connection' )]
	public function handleJson( Request $request, EntityManagerInterface $entityManager ): JsonResponse
	{
		$id         = $request->request->get( 'id' );
		$action     = $request->request->get( 'action' );
		$connection = ( $id && is_numeric( $id ) ) ? ConnectionService::getConnection( $id )
		                                                              ->getEntity() : new Connection();
		$json       = [];

		switch ( $action ) {
			case 'delete':
				// @todo
			break;
			case 'form':
			case 'create':
			case 'edit':
				$form = $this->form( $connection, $request, $entityManager, false );

				if ( $form->isSubmitted() ) {
					$json['success'] = $form->isValid();
				}

				$json['connection'] = $connection;
				$json['html']       = $this->render( '_partials/form.html.twig', [
					'form' => $form,
				] );
			break;
			default:
				$json = $connection;
			break;
		}

		return $this->json( $this->removeCircularReference( $json ) );
	}

	#[Route( '/connections', name: 'list_connections' )]
	public function list( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$connections = $entityManager->getRepository(Connection::class)->findAll();

		return $this->render( 'admin/connection/list.html.twig', [
			'connections' => $connections,
		] );
	}

	#[Route( '/connection/create', name: 'create_connection' )]
	public function create( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$connection = new Connection();
		$form       = $this->form( $connection, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully created connection!' );

			return $this->redirectToRoute( 'app_index' );
		}

		return $this->render( 'admin/connection/create.html.twig', [
			'form' => $form,
		] );
	}

	#[Route( '/connection/edit/{id}', name: 'edit_connection' )]
	public function edit( Connection $connection, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->form( $connection, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully created connection!' );

			return $this->redirectToRoute( 'app_index' );
		}

		return $this->render( 'admin/connection/edit.html.twig', [
			'form' => $form,
		] );
	}

	public function form( Connection $connection, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		$form = $this->createForm( ConnectionFormType::class, $connection, [ 'attr' => [ 'data-id' => $connection->getId() ] ] );
		if ( false !== $saveLabel ) {
			if ( ! $saveLabel ) {
				$saveLabel = ( $connection->getId() ) ? 'Update' : 'Create';
			}
			$form->add( 'save', SubmitType::class, [ 'label' => $saveLabel ] );
		}

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist( $connection );
			$entityManager->flush();
		}

		return $form;
	}
}
