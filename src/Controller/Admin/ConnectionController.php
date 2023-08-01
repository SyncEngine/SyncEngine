<?php

namespace App\Controller\Admin;

use App\Controller\EntityController;
use App\Entity\Connection;
use App\Form\ConnectionFormType;
use App\Model\ConnectionModel;
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
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? ConnectionModel::get( $id ) : new ConnectionModel( new Connection() );

		return $this->json( $this->_handleRequest( $model, $request, $entityManager ) );
	}

	#[Route( '/connections', name: 'list_connections' )]
	public function list(): Response
	{
		$model = new ConnectionModel( new Connection() );
		$query = [
			'limit' => 10,
			'total' => true,
			'relations' => true,
			'dependents' => true,
		];

		$items = $this->_handleActionList( $model, $query );

		return $this->render( 'admin/connection/list.html.twig', [
			'list' => [
				'query' => $query,
				'items' => $items,
				'total' => $this->_handleActionTotal( $model, $query ),
			],
			'breadcrumbs' => [
				[
					'title'   => 'Connections',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/connection/create', name: 'create_connection' )]
	public function create( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$connection = new Connection();
		$form       = $this->form( $connection, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully created connection!' );

			return $this->redirectToRoute( 'edit_connection', [ 'id' => $connection->getId() ] );
		}

		return $this->render( 'admin/connection/create.html.twig', [
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'list_connections' ),
					'title' => 'Connections',
				],
				[
					'title'   => 'Create',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/connection/edit/{id}', name: 'edit_connection' )]
	public function edit( Connection $connection, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->form( $connection, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully created connection!' );
		}

		return $this->render( 'admin/connection/edit.html.twig', [
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'list_connections' ),
					'title' => 'Connections',
				],
				[
					'title'   => 'Edit',
					'current' => true,
				],
			],
		] );
	}

	public function form( Connection|ConnectionModel $connection, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		if ( $connection instanceof Connection ) {
			$connection = new ConnectionModel( $connection );
		}
		return $this->_handleForm( $connection, ConnectionFormType::class, $request, $entityManager, $saveLabel );
	}
}
