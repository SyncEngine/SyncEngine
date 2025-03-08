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
use SyncEngine\Entity\Automation;
use SyncEngine\Form\AutomationFormType;
use SyncEngine\Model\AutomationModel;

class AutomationController extends EntityController
{
	#[Route( '/json/automation', 'json_automation' )]
	public function handleJson( Request $request ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? AutomationModel::get( $id ) : AutomationModel::create();

		return $this->_handleJsonRequest( $model, $request );
	}

	#[Route( '/automations', name: 'list_automations' )]
	#[MenuItem( menu: 'main', route: 'syncengine_list_automations', label: 'Automations', parent: 'entities', icon: 'automation', position: 2 )]
	public function renderList( Request $request ): Response
	{
		$model = AutomationModel::create();
		$query = $this->_getListQuery( $request );
		$items = $this->_handleActionList( $model, $query );

		return $this->render(
			'admin/automation/list.html.twig',
			[
				'list'        => [
					'query' => $query,
					'items' => $items,
					'total' => $this->_handleActionTotal( $model, $query ),
				],
				'breadcrumbs' => [
					[
						'title'   => $this->trans( 'Automations' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/automation/create', name: 'create_automation' )]
	public function renderCreate( Request $request ): Response
	{
		$automation = AutomationModel::create();
		$form       = $this->form( $automation, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully created automation!' ) );

			return $this->redirectToRoute( 'syncengine_edit_automation', [ 'id' => $automation->getId() ] );
		}

		return $this->render(
			'admin/automation/create.html.twig',
			[
				'header'      => $this->trans( 'New automation' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_automations' ),
						'title' => $this->trans( 'Automations' ),
					],
					[
						'title'   => $this->trans( 'Create' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/automation/edit/{id}', name: 'edit_automation' )]
	public function renderEdit( #[MapEntity(id: 'id')] Automation $automation, Request $request ): Response
	{
		$form = $this->form( $automation, $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', $this->trans( 'Successfully edited automation!' ) );
		}

		return $this->render(
			'admin/automation/edit.html.twig',
			[
				'header'      => $this->trans( 'Edit automation' ),
				'backlink'    => true,
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_list_automations' ),
						'title' => $this->trans( 'Automations' ),
					],
					[
						'title'   => $this->trans( 'Edit' ),
						'current' => true,
					],
				],
			]
		);
	}

	public function form( Automation|AutomationModel $automation, Request $request, $saveLabel = '' ): FormInterface|bool
	{
		if ( $automation instanceof Automation ) {
			$automation = AutomationModel::get( $automation );
		}

		return $this->_handleForm( $automation, AutomationFormType::class, $request, $saveLabel );
	}

	public function reset( AutomationModel $automation ): array
	{
		$automation->reset();
		$automation->persist( true );

		return [
			'success' => true,
			'close'   => true,
		];
	}
}
