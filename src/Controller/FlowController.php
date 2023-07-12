<?php

namespace App\Controller;

use App\Entity\Flow;
use App\Entity\Step;
use App\Form\FlowFormType;
use App\Form\Type\JsonType;
use App\Service\FlowService;
use App\Service\StepService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FlowController extends EntityController
{
	#[Route( '/flow/json', 'json_flow' )]
	public function handleJson( Request $request, EntityManagerInterface $entityManager ): JsonResponse
	{
		$id     = $request->request->get( 'id' );
		$action = $request->request->get( 'action' );
		$flow   = ( $id && is_numeric( $id ) ) ? FlowService::getFlow( $id )->getEntity() : new Flow();
		$json   = [];

		switch ( $action ) {
			case 'delete':
				// @todo
			break;
			case 'form':
			case 'create':
			case 'edit':
				$form = $this->form( $flow, $request, $entityManager, false );

				if ( $form->isSubmitted() ) {
					$json['success'] = $form->isValid();
				}

				$json['flow'] = $flow;
				$json['html'] = $this->render( '_partials/form.html.twig', [
					'form' => $form,
				] );
			break;
			default:
				$json = $flow;
			break;
		}

		return $this->json( $this->removeCircularReference( $json ) );
	}

	#[Route( '/flows', name: 'list_flows' )]
	public function list( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$flows = $entityManager->getRepository( Flow::class )->findAll();
		$steps = $entityManager->getRepository( Step::class )->findAll();

		return $this->render( 'admin/flow/list.html.twig', [
			'flows' => $flows,
			'steps' => $steps,
		] );
	}

	#[Route( '/flow/create', name: 'create_flow' )]
	public function create( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$flow = new Flow();
		$form = $this->form( $flow, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully added flow!' );

			return $this->redirectToRoute( 'edit_flow', [ 'id' => $flow->getId() ] );
		}

		return $this->render( 'admin/flow/create.html.twig', [
			'form' => $form,
		] );
	}

	#[Route( '/flow/edit/{id}', name: 'edit_flow' )]
	public function edit( Flow $flow, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->form( $flow, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully edited flow!' );

			return $this->redirectToRoute( 'app_index' );
		}

		return $this->render( 'flow/edit.html.twig', [
			'flow' => $flow,
			'form' => $form,
		] );
	}

	public function form( Flow $flow, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		$steps = [];
		foreach ( StepService::getSteps() as $step ) {
			$steps[ $step->getId() ] = [
				'id'          => $step->getId(),
				'name'        => $step->getName(),
				'description' => $step->getDescription(),
				'config'      => $step->getConfig(),
			];
		}

		$form = $this->createForm( FlowFormType::class, $flow, [ 'attr' => [ 'data-id' => $flow->getId() ] ] );
		$form->add( 'steps', JsonType::class, [
			'data'     => $flow->getSteps(),
			'row_attr' => [
				'class' => 'form-floating mb-3',
			],
			'attr'     => [
				'data-controller' => 'react',
				'data-type'       => 'flow',
				'data-args'       => json_encode( [
					'order'    => $flow->getSteps(),
					'steps'    => $steps,
					// @todo Move all endpoints to a global var.
					'endpoint' => $this->generateUrl( 'json_step' ),
				] ),
			],
		] );

		if ( false !== $saveLabel ) {
			if ( ! $saveLabel ) {
				$saveLabel = ( $flow->getId() ) ? 'Update' : 'Create';
			}
			$form->add( 'save', SubmitType::class, [ 'label' => $saveLabel ] );
		}

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$formData = $form->getData();

			$steps = [];
			foreach ( $formData->getSteps() as $stepID ) {
				if ( $entityManager->getRepository( Step::class )->findOneBy( [ 'id' => $stepID ] ) ) {
					$steps[] = $stepID;
				}
			}

			$flow->setSteps( $steps );

			$entityManager->persist( $flow );
			$entityManager->flush();
		}

		return $form;
	}
}
