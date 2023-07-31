<?php

namespace App\Controller\Admin;

use App\Controller\EntityController;
use App\Entity\Flow;
use App\Entity\Step;
use App\Form\FlowFormType;
use App\Form\Type\JsonType;
use App\Model\FlowModel;
use App\Model\StepModel;
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
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? FlowModel::get( $id ) : new FlowModel( new Flow() );

		return $this->json( $this->_handleJson( $model, $request, $entityManager ) );
	}

	#[Route( '/flows', name: 'list_flows' )]
	public function list(): Response
	{
		return $this->render( 'admin/flow/list.html.twig', [
			'breadcrumbs' => [
				[
					'title'   => 'Flows',
					'current' => true,
				],
			],
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
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'list_flows' ),
					'title' => 'Flows',
				],
				[
					'title'   => 'Create',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/flow/edit/{id}', name: 'edit_flow' )]
	public function edit( Flow $flow, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->form( $flow, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully edited flow!' );
		}

		return $this->render( 'admin/flow/edit.html.twig', [
			'flow'        => $flow,
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'list_flows' ),
					'title' => 'Flows',
				],
				[
					'title'   => 'Edit',
					'current' => true,
				],
			],
		] );
	}

	public function form( Flow|FlowModel $flow, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		if ( $flow instanceof FlowModel ) {
			$flow = $flow->getEntity();
		}

		$steps = [];
		foreach ( StepModel::getAll() as $step ) {
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
			$flowModel = new FlowModel( $flow );

			$formData = $form->getData();

			$steps = [];
			foreach ( $formData->getSteps() as $stepID ) {
				if ( $entityManager->getRepository( Step::class )->findOneBy( [ 'id' => $stepID ] ) ) {
					$steps[] = $stepID;
				}
			}

			$flowModel->setSteps( $steps );

			$flowModel->persist( $entityManager, true );
		}

		return $form;
	}
}
