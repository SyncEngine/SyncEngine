<?php

namespace App\Controller\Admin;

use App\Controller\EntityController;
use App\Entity\Dataset;
use App\Form\DatasetFormType;
use App\Model\DatasetModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DatasetController extends EntityController
{
	#[Route( '/dataset/json', 'json_dataset' )]
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
				$dataset = ( $id ) ? DatasetModel::get( $id )->getEntity() : new Dataset();

				$form = $this->form( $dataset, $request, $entityManager, false );

				if ( $form->isSubmitted() ) {
					$json['success'] = $form->isValid();
				}

				$json['dataset'] = $dataset;
				$json['html']    = $this->render( '_partials/form.html.twig', [
					'form' => $form,
				] );
			break;

			case 'export':
				if ( $id ) {
					$json['success'] = true;
					$json['export']  = DatasetModel::get( $id )->export();
				} else {
					$json['success'] = false;
				}
			break;

			case 'query':
			case 'list':
				$query   = $request->request->get( 'query' );
				$query   = $query ? json_decode( $query, true ) : null;
				$results = DatasetModel::getAll( $query );

				if ( $results ) {
					foreach ( $results as $key => $item ) {
						$results[ $key ] = $item->normalize( $query['relations'] ?? false, $query['dependents'] ?? false );
					}
				}

				if ( ! empty( $query['total'] ) ) {
					$json['total'] = DatasetModel::getTotalCount( $query );
				}

				$json['data']    = $results;
				$json['success'] = true;
			break;
		}

		return $this->json( $json );
	}

	#[Route( '/datasets', name: 'list_datasets' )]
	public function list(): Response
	{
		return $this->render( 'admin/dataset/list.html.twig', [
			'breadcrumbs' => [
				[
					'title'   => 'Datasets',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/dataset/create', name: 'create_dataset' )]
	public function create( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$dataset = new Dataset();
		$form    = $this->form( $dataset, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully created dataset!' );

			return $this->redirectToRoute( 'edit_dataset', [ 'id' => $dataset->getId() ] );
		}

		return $this->render( 'admin/dataset/create.html.twig', [
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'list_datasets' ),
					'title' => 'Datasets',
				],
				[
					'title'   => 'Create',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/dataset/edit/{id}', name: 'edit_dataset' )]
	public function edit( Dataset $dataset, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->form( $dataset, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully created dataset!' );

			return $this->redirectToRoute( 'app_index' );
		}

		return $this->render( 'admin/dataset/edit.html.twig', [
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'list_datasets' ),
					'title' => 'Datasets',
				],
				[
					'title'   => 'Edit',
					'current' => true,
				],
			],
		] );
	}

	public function form( Dataset $dataset, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		$form = $this->createForm( DatasetFormType::class, $dataset, [ 'attr' => [ 'data-id' => $dataset->getId() ] ] );
		if ( false !== $saveLabel ) {
			if ( ! $saveLabel ) {
				$saveLabel = ( $dataset->getId() ) ? 'Update' : 'Create';
			}
			$form->add( 'save', SubmitType::class, [ 'label' => $saveLabel ] );
		}

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$datasetModel = new DatasetModel( $dataset );
			$datasetModel->persist( $entityManager, true );
		}

		return $form;
	}
}
