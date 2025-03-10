<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Attribute\MenuItem;
use SyncEngine\Controller\Admin\Abstract\EntityController;
use SyncEngine\Entity\Storage;
use SyncEngine\Form\StorageFormType;
use SyncEngine\Model\StorageModel;

class StorageController extends EntityController
{
	#[Route( '/json/storage', 'json_storage' )]
	public function handleJson( Request $request ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? StorageModel::get( $id ) : StorageModel::create();

		return $this->_handleJsonRequest( $model, $request );
	}

	#[Route( '/storages', name: 'list_storages' )]
	#[MenuItem( menu: 'main', route: 'syncengine_list_storages', label: 'Storages', parent: 'entities', icon: 'storage', position: 5 )]
	public function renderList( Request $request ): Response
	{
		$model = StorageModel::create();
		$query = $this->_getListQuery( $request );
		$items = $this->_handleActionList( $model, $query );

		return $this->render(
			'admin/storage/list.html.twig',
			[
				'list'        => [
					'query' => $query,
					'items' => $items,
					'total' => $this->_handleActionTotal( $model, $query ),
				],
				'breadcrumbs' => [
					[
						'title'   => $this->trans( 'Storages' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/storage/create', name: 'create_storage' )]
	public function renderCreate( Request $request ): Response
	{
		$storage = StorageModel::create();
		$form    = $this->form( $storage, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully created storage!' ) );

			return $this->redirectToRoute( 'syncengine_edit_storage', [ 'id' => $storage->getId() ] );
		}

		return $this->render(
			'admin/storage/create.html.twig',
			[
				'header'      => $this->trans( 'New storage' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_storages' ),
						'title' => $this->trans( 'Storages' ),
					],
					[
						'title'   => $this->trans( 'Create' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/storage/edit/{id}', name: 'edit_storage' )]
	public function renderEdit( #[MapEntity(id: 'id')] Storage $storage, Request $request ): Response
	{
		$form = $this->form( $storage, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully edited storage!' ) );
		}

		return $this->render(
			'admin/storage/edit.html.twig',
			[
				'header'      => $this->trans( 'Edit storage' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_storages' ),
						'title' => $this->trans( 'Storages' ),
					],
					[
						'title'   => $this->trans( 'Edit' ),
						'current' => true,
					],
				],
			]
		);
	}

	public function form( Storage|StorageModel $storage, Request $request, $saveLabel = '' ): FormInterface
	{
		if ( $storage instanceof Storage ) {
			$storage = StorageModel::get( $storage );
		}

		return $this->_handleForm( $storage, StorageFormType::class, $request, $saveLabel );
	}
}
