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
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? DatasetModel::get( $id ) : new DatasetModel( new Dataset() );

		return $this->json( $this->_handleRequest( $model, $request, $entityManager ) );
	}

	#[Route( '/datasets', name: 'list_datasets' )]
	public function renderList(): Response
	{
		$model = new DatasetModel( new Dataset() );
		$query = [
			'limit' => 10,
			'total' => true,
			'relations' => true,
		];

		$items = $this->_handleActionList( $model, $query );

		return $this->render( 'admin/dataset/list.html.twig', [
			'list' => [
				'query' => $query,
				'items' => $items,
				'total' => $this->_handleActionTotal( $model, $query ),
			],
			'breadcrumbs' => [
				[
					'title'   => 'Datasets',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/dataset/create', name: 'create_dataset' )]
	public function renderCreate( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$dataset = new Dataset();
		$form    = $this->form( $dataset, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully created dataset!' );

			return $this->redirectToRoute( 'edit_dataset', [ 'id' => $dataset->getId() ] );
		}

		return $this->render( 'admin/dataset/create.html.twig', [
			'header'      => 'New dataset',
			'backlink'    => true,
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
	public function renderEdit( Dataset $dataset, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->form( $dataset, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully created dataset!' );

			return $this->redirectToRoute( 'app_index' );
		}

		return $this->render( 'admin/dataset/edit.html.twig', [
			'header'      => 'Edit dataset',
			'backlink'    => true,
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

	public function form( Dataset|DatasetModel $dataset, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		if ( $dataset instanceof Dataset ) {
			$dataset = new DatasetModel( $dataset );
		}
		return $this->_handleForm( $dataset, DatasetFormType::class, $request, $entityManager, $saveLabel );
	}
}
