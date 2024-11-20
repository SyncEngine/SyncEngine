<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Attribute\MenuItem;
use SyncEngine\Controller\Admin\Abstract\EntityController;
use SyncEngine\Entity\Connection;
use SyncEngine\Form\ConnectionFormType;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Service\Vault;

class ConnectionController extends EntityController
{
	public static function getSubscribedServices(): array
	{
		return array_merge(
			parent::getSubscribedServices(),
			[
				'Vault' => '?' . Vault::class,
			]
		);
	}

	#[Route( '/json/connection', 'json_connection' )]
	public function handleJson( Request $request ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? ConnectionModel::get( $id ) : ConnectionModel::create();
		$model->setContainer( $this->container );

		return $this->_handleJsonRequest( $model, $request );
	}

	#[Route( '/connections', name: 'list_connections' )]
	#[MenuItem( menu: 'main', route: 'syncengine_list_connections', label: 'Connections', parent: 'entities', icon: 'connection', position: 1 )]
	public function renderList( Request $request ): Response
	{
		$model = ConnectionModel::create();
		$query = $this->_getListQuery( $request );
		$items = $this->_handleActionList( $model, $query );

		return $this->render(
			'admin/connection/list.html.twig',
			[
				'list'        => [
					'query' => $query,
					'items' => $items,
					'total' => $this->_handleActionTotal( $model, $query ),
				],
				'breadcrumbs' => [
					[
						'title'   => $this->trans( 'Connections' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/connection/create', name: 'create_connection' )]
	public function renderCreate( Request $request ): Response
	{
		$connection = ConnectionModel::create();
		$form       = $this->form( $connection, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully created connection!' ) );

			return $this->redirectToRoute( 'syncengine_edit_connection', [ 'id' => $connection->getId() ] );
		}

		return $this->render(
			'admin/connection/create.html.twig',
			[
				'header'      => $this->trans( 'New connection' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_connections' ),
						'title' => $this->trans( 'Connections' ),
					],
					[
						'title'   => $this->trans( 'Create' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/connection/edit/{id}', name: 'edit_connection' )]
	public function renderEdit( Connection $connection, Request $request ): Response
	{
		$form = $this->form( $connection, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully created connection!' ) );
		}

		return $this->render(
			'admin/connection/edit.html.twig',
			[
				'header'      => $this->trans( 'Edit connection' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_connections' ),
						'title' => $this->trans( 'Connections' ),
					],
					[
						'title'   => $this->trans( 'Edit' ),
						'current' => true,
					],
				],
			]
		);
	}

	public function form( Connection|ConnectionModel $connection, Request $request, $saveLabel = '' ): FormInterface|bool
	{
		if ( $connection instanceof Connection ) {
			$connection = ConnectionModel::get( $connection );
		}

		return $this->_handleForm( $connection, ConnectionFormType::class, $request, $saveLabel );
	}
}
