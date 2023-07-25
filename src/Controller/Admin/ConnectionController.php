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
		$id     = $request->request->get( 'id' );
		$id     = ( $id && is_numeric( $id ) ) ? $id : 0;
		$action = $request->request->get( 'action' );
		$json   = [];

		switch ( $action ) {
			case 'delete':
				// @todo
			break;

			case 'form':
			case 'create':
			case 'edit':
				$connection = ( $id ) ? ConnectionModel::get( $id )->getEntity() : new Connection();

				$form = $this->form( $connection, $request, $entityManager, false );

				if ( $form->isSubmitted() ) {
					$json['success'] = $form->isValid();
				}

				$json['connection'] = $connection;
				$json['html']       = $this->render( '_partials/form.html.twig', [
					'form' => $form,
				] );
			break;

			case 'export':
				if ( $id ) {
					$json['success'] = true;
					$json['export']  = ConnectionModel::get( $id )->export();
				} else {
					$json['success'] = false;
				}
			break;

			case 'query':
			case 'list':
				$query   = $request->request->get( 'query' );
				$query   = $query ? json_decode( $query, true ) : null;
				$results = ConnectionModel::getAll( $query );

				if ( $results ) {
					foreach ( $results as $key => $item ) {
						$results[ $key ] = $item->normalize( $query['relations'] ?? false, $query['dependents'] ?? false );
					}
				}

				if ( ! empty( $query['total'] ) ) {
					$json['total'] = ConnectionModel::getTotalCount( $query );
				}

				$json['data']    = $results;
				$json['success'] = true;
			break;
		}

		return $this->json( $json );
	}

	#[Route( '/connections', name: 'list_connections' )]
	public function list(): Response
	{
		return $this->render( 'admin/connection/list.html.twig', [
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
			$connectionModel = new ConnectionModel( $connection );
			$connectionModel->persist( $entityManager, true );
		}

		return $form;
	}
}
