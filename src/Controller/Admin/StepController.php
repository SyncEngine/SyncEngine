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
use SyncEngine\Entity\Step;
use SyncEngine\Form\StepFormType;
use SyncEngine\Model\StepModel;

class StepController extends EntityController
{
	#[Route( '/json/step', 'json_step' )]
	public function handleJson( Request $request ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? StepModel::get( $id ) : StepModel::create();

		return $this->_handleJsonRequest( $model, $request );
	}

	#[Route( '/steps', name: 'list_steps' )]
	#[MenuItem( menu: 'main', route: 'syncengine_list_steps', label: 'Steps', parent: 'entities', icon: 'step', position: 4 )]
	public function renderList( Request $request ): Response
	{
		$model = StepModel::create();
		$query = $this->_getListQuery( $request );
		$items = $this->_handleActionList( $model, $query );

		return $this->render(
			'admin/step/list.html.twig',
			[
				'list'        => [
					'query' => $query,
					'items' => $items,
					'total' => $this->_handleActionTotal( $model, $query ),
				],
				'breadcrumbs' => [
					[
						'title'   => $this->trans( 'Steps' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/step/create', name: 'create_step' )]
	public function renderCreate( Request $request ): Response
	{
		$step = StepModel::create();
		$form = $this->form( $step, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully created step!' ) );

			return $this->redirectToRoute( 'syncengine_edit_step', [ 'íd' => $step->getId() ] );
		}

		return $this->render(
			'admin/step/create.html.twig',
			[
				'header'      => $this->trans( 'New step' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_steps' ),
						'title' => $this->trans( 'Steps' ),
					],
					[
						'title'   => $this->trans( 'Create' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/step/edit/{id}', name: 'edit_step' )]
	public function renderEdit( #[MapEntity(id: 'id')] Step $step, Request $request ): Response
	{
		$form = $this->form( $step, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully edited step!' ) );
		}

		return $this->render(
			'admin/step/edit.html.twig',
			[
				'header'      => $this->trans( 'Edit step' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_steps' ),
						'title' => $this->trans( 'Steps' ),
					],
					[
						'title'   => $this->trans( 'Edit' ),
						'current' => true,
					],
				],
			]
		);
	}

	protected function form( Step|StepModel $step, Request $request, $saveLabel = '' ): FormInterface
	{
		if ( $step instanceof Step ) {
			$step = StepModel::get( $step );
		}

		return $this->_handleForm( $step, StepFormType::class, $request, $saveLabel );
	}
}
