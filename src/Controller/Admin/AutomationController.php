<?php

namespace App\Controller\Admin;

use App\Controller\Abstract\EntityController;
use App\Entity\Automation;
use App\Entity\Flow;
use App\Form\AutomationFormType;
use App\Model\AutomationModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AutomationController extends EntityController
{
	#[Route( '/json/automation', 'json_automation' )]
	public function handleJson( Request $request, EntityManagerInterface $entityManager ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? AutomationModel::get( $id ) : new AutomationModel( new Automation() );

		return $this->json( $this->_handleRequest( $model, $request, $entityManager ) );
	}

	#[Route( '/automations', name: 'list_automations' )]
	public function renderList(): Response
	{
		$model = new AutomationModel( new Automation() );
		$query = [
			'limit' => 10,
			'total' => true,
			'relations' => true,
		];

		$items = $this->_handleActionList( $model, $query );

		return $this->render( 'admin/automation/list.html.twig', [
			'list' => [
				'query' => $query,
				'items' => $items,
				'total' => $this->_handleActionTotal( $model, $query ),
			],
			'breadcrumbs' => [
				[
					'title'   => 'Automations',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/automation/create', name: 'create_automation' )]
	public function renderCreate( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$automation = new Automation();
		$form       = $this->form( $automation, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully created automation!' );

			return $this->redirectToRoute( 'edit_automation', [ 'id' => $automation->getId() ] );
		}

		return $this->render( 'admin/automation/create.html.twig', [
			'header'      => 'New automation',
			'backlink'    => true,
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'list_automations' ),
					'title' => 'Automations',
				],
				[
					'title'   => 'Create',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/automation/edit/{id}', name: 'edit_automation' )]
	public function renderEdit( Automation $automation, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->form( $automation, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully edited automation!' );
		}

		return $this->render( 'admin/automation/edit.html.twig', [
			'header'      => 'Edit automation',
			'backlink'    => true,
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'list_automations' ),
					'title' => 'Automations',
				],
				[
					'title'   => 'Edit',
					'current' => true,
				],
			],
		] );
	}

	public function form( Automation|AutomationModel $automation, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		if ( $automation instanceof Automation ) {
			$automation = new AutomationModel( $automation );
		}
		return $this->_handleForm( $automation, AutomationFormType::class, $request, $entityManager, $saveLabel );
	}
}
