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

		return $this->json( $this->_handleRequest( $model, $request, $entityManager ) );
	}

	#[Route( '/flows', name: 'list_flows' )]
	public function renderList(): Response
	{
		$model = new FlowModel( new Flow() );
		$query = [
			'limit' => 10,
			'total' => true,
			'relations' => true,
		];

		$items = $this->_handleActionList( $model, $query );

		return $this->render( 'admin/flow/list.html.twig', [
			'list' => [
				'query' => $query,
				'items' => $items,
				'total' => $this->_handleActionTotal( $model, $query ),
			],
			'breadcrumbs' => [
				[
					'title'   => 'Flows',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/flow/create', name: 'create_flow' )]
	public function renderCreate( Request $request, EntityManagerInterface $entityManager ): Response
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
	public function renderEdit( Flow $flow, Request $request, EntityManagerInterface $entityManager ): Response
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
		if ( $flow instanceof Flow ) {
			$flow = new FlowModel( $flow );
		}
		return $this->_handleForm( $flow, FlowFormType::class, $request, $entityManager, $saveLabel );
	}
}
