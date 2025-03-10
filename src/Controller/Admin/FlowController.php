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
use SyncEngine\Entity\Flow;
use SyncEngine\Form\FlowFormType;
use SyncEngine\Model\FlowModel;

class FlowController extends EntityController
{
	#[Route( '/json/flow', 'json_flow' )]
	public function handleJson( Request $request ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? FlowModel::get( $id ) : FlowModel::create();

		return $this->_handleJsonRequest( $model, $request );
	}

	#[Route( '/flows', name: 'list_flows' )]
	#[MenuItem( menu: 'main', route: 'syncengine_list_flows', label: 'Flows', parent: 'entities', icon: 'flow', position: 3 )]
	public function renderList( Request $request ): Response
	{
		$model = FlowModel::create();
		$query = $this->_getListQuery( $request );
		$items = $this->_handleActionList( $model, $query );

		return $this->render(
			'admin/flow/list.html.twig',
			[
				'list'        => [
					'query' => $query,
					'items' => $items,
					'total' => $this->_handleActionTotal( $model, $query ),
				],
				'breadcrumbs' => [
					[
						'title'   => $this->trans( 'Flows' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/flow/create', name: 'create_flow' )]
	public function renderCreate( Request $request ): Response
	{
		$flow = FlowModel::create();
		$form = $this->form( $flow, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully added flow!' ) );

			return $this->redirectToRoute( 'syncengine_edit_flow', [ 'id' => $flow->getId() ] );
		}

		return $this->render(
			'admin/flow/create.html.twig',
			[
				'header'      => $this->trans( 'New flow' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_flows' ),
						'title' => $this->trans( 'Flows' ),
					],
					[
						'title'   => $this->trans( 'Create' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/flow/edit/{id}', name: 'edit_flow' )]
	public function renderEdit( #[MapEntity(id: 'id')] Flow $flow, Request $request ): Response
	{
		$form = $this->form( $flow, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully edited flow!' ) );
		}

		return $this->render(
			'admin/flow/edit.html.twig',
			[
				'header'      => $this->trans( 'Edit flow' ),
				'backlink'    => true,
				'flow'        => $flow,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_flows' ),
						'title' => $this->trans( 'Flows' ),
					],
					[
						'title'   => $this->trans( 'Edit' ),
						'current' => true,
					],
				],
			]
		);
	}

	protected function form( Flow|FlowModel $flow, Request $request, $saveLabel = '' ): FormInterface
	{
		if ( $flow instanceof Flow ) {
			$flow = FlowModel::get( $flow );
		}

		return $this->_handleForm( $flow, FlowFormType::class, $request, $saveLabel );
	}
}
