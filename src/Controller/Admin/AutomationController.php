<?php

namespace App\Controller\Admin;

use App\Controller\EntityController;
use App\Entity\Automation;
use App\Entity\Flow;
use App\Form\AutomationFormType;
use App\Model\AutomationModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AutomationController extends EntityController
{
	#[Route( '/automation/json', 'json_automation' )]
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
				$automation = ( $id ) ? AutomationModel::get( $id )->getEntity() : new Automation();

				$form = $this->form( $automation, $request, $entityManager, false );

				if ( $form->isSubmitted() ) {
					$json['success'] = $form->isValid();
				}

				$json['automation'] = $automation;
				$json['html']       = $this->render( '_partials/form.html.twig', [
					'form' => $form,
				] );
			break;

			case 'export':
				if ( $id ) {
					$json['success'] = true;
					$json['export']  = AutomationModel::get( $id )->export();
				} else {
					$json['success'] = false;
				}
			break;

			case 'query':
			case 'list':
				$query   = $request->request->get( 'query' );
				$query   = $query ? json_decode( $query, true ) : null;
				$results = AutomationModel::getAll( $query );

				if ( $results ) {
					foreach ( $results as $key => $item ) {
						$results[ $key ] = $item->normalize( $query['relations'] ?? false, $query['dependents'] ?? false );
					}
				}

				if ( ! empty( $query['total'] ) ) {
					$json['total'] = AutomationModel::getTotalCount( $query );
				}

				$json['data']    = $results;
				$json['success'] = true;
			break;
		}

		return $this->json( $json );
	}

	#[Route( '/automations', name: 'list_automations' )]
	public function list(): Response
	{
		return $this->render( 'admin/automation/list.html.twig', [
			'breadcrumbs' => [
				[
					'link'    => $this->generateUrl( 'list_automations' ),
					'title'   => 'Automations',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/automation/create', name: 'create_automation' )]
	public function create( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$automation = new Automation();
		$form       = $this->form( $automation, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully created automation!' );

			return $this->redirectToRoute( 'edit_automation', [ 'id' => $automation->getId() ] );
		}

		// @todo allow creating flows within the form.
		$flows = $entityManager->getRepository( Flow::class )->findAll();
		if ( ! $flows ) {
			$this->addFlash( 'warning', 'You first need to create a flow' );

			return $this->redirectToRoute( 'create_flow' );
		}

		return $this->render( 'admin/automation/create.html.twig', [
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
	public function newAutomation( Automation $automation, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->form( $automation, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully edited automation!' );
		}

		return $this->render( 'admin/automation/edit.html.twig', [
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

	public function form( Automation $automation, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		$form = $this->createForm( AutomationFormType::class, $automation, [ 'attr' => [ 'data-id' => $automation->getId() ] ] );
		if ( false !== $saveLabel ) {
			if ( ! $saveLabel ) {
				$saveLabel = ( $automation->getId() ) ? 'Update' : 'Create';
			}
			$form->add( 'save', SubmitType::class, [ 'label' => $saveLabel ] );
		}

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$automationModel = new AutomationModel( $automation );

			$endpoint = $this->slugify( $automation->getEndpoint() );
			$automationModel->setEndpoint( $endpoint );

			$automationModel->persist( $entityManager, true );
		}

		return $form;
	}
}
