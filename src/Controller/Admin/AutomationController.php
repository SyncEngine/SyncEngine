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
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? AutomationModel::get( $id ) : new AutomationModel( new Automation() );

		return $this->json( $this->_handleJson( $model, $request, $entityManager ) );
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

	public function form( Automation|AutomationModel $automation, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		if ( $automation instanceof Automation ) {
			$automation = new AutomationModel( $automation );
		}
		return $this->_handleForm( $automation, AutomationFormType::class, $request, $entityManager, $saveLabel );
	}
}
