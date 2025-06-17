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
use SyncEngine\Entity\Routine;
use SyncEngine\Form\RoutineFormType;
use SyncEngine\Model\RoutineModel;

class RoutineController extends EntityController
{
	#[Route( '/json/routine', 'json_routine' )]
	public function handleJson( Request $request ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? RoutineModel::get( $id ) : RoutineModel::create();

		return $this->_handleJsonRequest( $model, $request );
	}

	#[Route( '/routines', name: 'list_routines' )]
	#[MenuItem( menu: 'main', route: 'syncengine_list_routines', label: 'Routines', parent: 'entities', icon: 'routine', position: 4 )]
	public function renderList( Request $request ): Response
	{
		$model = RoutineModel::create();
		$query = $this->_getListQuery( $request );
		$items = $this->_handleActionList( $model, $query );

		return $this->render(
			'admin/routine/list.html.twig',
			[
				'list'        => [
					'query' => $query,
					'items' => $items,
					'total' => $this->_handleActionTotal( $model, $query ),
				],
				'breadcrumbs' => [
					[
						'title'   => $this->trans( 'Routines' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/routine/create', name: 'create_routine' )]
	public function renderCreate( Request $request ): Response
	{
		$routine = RoutineModel::create();
		$form = $this->form( $routine, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully created routine!' ) );

			return $this->redirectToRoute( 'syncengine_edit_routine', [ 'id' => $routine->getId() ] );
		}

		return $this->render(
			'admin/routine/create.html.twig',
			[
				'header'      => $this->trans( 'New routine' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_routines' ),
						'title' => $this->trans( 'Routines' ),
					],
					[
						'title'   => $this->trans( 'Create' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/routine/edit/{id}', name: 'edit_routine' )]
	public function renderEdit( #[MapEntity(id: 'id')] Routine $routine, Request $request ): Response
	{
		$form = $this->form( $routine, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully edited routine!' ) );
		}

		return $this->render(
			'admin/routine/edit.html.twig',
			[
				'header'      => $this->trans( 'Edit routine' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_routines' ),
						'title' => $this->trans( 'Routines' ),
					],
					[
						'title'   => $this->trans( 'Edit' ),
						'current' => true,
					],
				],
			]
		);
	}

	protected function form( Routine|RoutineModel $routine, Request $request, $saveLabel = '' ): FormInterface
	{
		if ( $routine instanceof Routine ) {
			$routine = RoutineModel::get( $routine );
		}

		return $this->_handleForm( $routine, RoutineFormType::class, $request, $saveLabel );
	}
}
