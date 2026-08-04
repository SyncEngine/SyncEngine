<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use SyncEngine\Attribute\MenuItem;
use SyncEngine\Controller\Admin\Abstract\EntityController;
use SyncEngine\Entity\Storage;
use SyncEngine\Form\StorageFormType;
use SyncEngine\Model\StorageModel;

/**
 * @extends EntityController<StorageModel>
 */
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

	#[Route( '/storages', name: 'storage_list' )]
	#[MenuItem( menu: 'main', route: 'syncengine_storage_list', label: 'Storages', parent: 'entities', icon: 'storage', position: 5 )]
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

	#[Route( '/storage/create', name: 'storage_create' )]
	#[IsGranted( 'ROLE_EDITOR' )]
	public function renderCreate( Request $request ): Response
	{
		$response = new Response();
		$storage  = StorageModel::create();
		$form     = $this->form( $storage, $request );

		if ( $form->isSubmitted() ) {
			if ( $form->isValid() ) {
				$this->addFlash( 'success', $this->trans( 'Successfully created storage!' ) );

				return $this->redirectToRoute( 'syncengine_storage_edit', [ 'id' => $storage->getId() ] );
			}
			$response->setStatusCode( Response::HTTP_UNPROCESSABLE_ENTITY );
		}

		return $this->render(
			'admin/storage/create.html.twig',
			[
				'header'      => $this->trans( 'New storage' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_storage_list' ),
						'title' => $this->trans( 'Storages' ),
					],
					[
						'title'   => $this->trans( 'Create' ),
						'current' => true,
					],
				],
			],
			$response
		);
	}

	#[Route( '/storage/edit/{id}', name: 'storage_edit' )]
	#[IsGranted( 'ROLE_EDITOR' )]
	public function renderEdit( #[MapEntity(id: 'id')] Storage $storage, Request $request ): Response
	{
		$response = new Response();
		$form     = $this->form( $storage, $request );

		if ( $form->isSubmitted() ) {
			if ( $form->isValid() ) {
				$this->addFlash( 'success', $this->trans( 'Successfully edited storage!' ) );
				$response->setStatusCode( Response::HTTP_ACCEPTED );
			} else {
				$response->setStatusCode( Response::HTTP_UNPROCESSABLE_ENTITY );
			}
		}

		return $this->render(
			'admin/storage/edit.html.twig',
			[
				'header'      => $this->trans( 'Edit storage' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_storage_list' ),
						'title' => $this->trans( 'Storages' ),
					],
					[
						'title'   => $this->trans( 'Edit' ),
						'current' => true,
					],
				],
			],
			$response
		);
	}

	protected function form( Storage|StorageModel $storage, Request $request, false|string $saveLabel = '' ): FormInterface
	{
		if ( $storage instanceof Storage ) {
			$storage = StorageModel::get( $storage );
		}

		return $this->_handleForm( $storage, StorageFormType::class, $request, $saveLabel );
	}
}
