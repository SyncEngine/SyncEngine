<?php

namespace App\Controller\Admin;

use App\Controller\EntityController;
use App\Entity\Flow;
use App\Entity\Step;
use App\Form\StepFormType;
use App\Model\StepModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StepController extends EntityController
{
	#[Route( '/step/json', 'json_step' )]
	public function handleJson( Request $request, EntityManagerInterface $entityManager ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? StepModel::get( $id ) : new StepModel( new Step() );

		return $this->json( $this->_handleJson( $model, $request, $entityManager ) );
	}

	#[Route( '/steps', name: 'list_steps' )]
	public function list(): Response
	{
		return $this->render( 'admin/step/list.html.twig', [
			'breadcrumbs' => [
				[
					'title'   => 'Steps',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/step/create', name: 'create_step' )]
	public function create( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$step = new Step();
		$form = $this->form( $step, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully created step!' );

			return $this->redirectToRoute( 'edit_step', [ 'íd' => $step->getId() ] );
		}

		return $this->render( 'admin/step/create.html.twig', [
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'list_steps' ),
					'title' => 'Steps',
				],
				[
					'title'   => 'Create',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/step/edit/{id}', name: 'edit_step' )]
	public function edit( Step $step, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->form( $step, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash( 'success', 'Successfully edited step!' );
		}

		return $this->render( 'admin/step/edit.html.twig', [
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'list_steps' ),
					'title' => 'Steps',
				],
				[
					'title'   => 'Edit',
					'current' => true,
				],
			],
		] );
	}

	public function form( Step $step, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		$form = $this->createForm( StepFormType::class, $step, [ 'attr' => [ 'data-id' => $step->getId() ] ] );
		if ( false !== $saveLabel ) {
			if ( ! $saveLabel ) {
				$saveLabel = ( $step->getId() ) ? 'Update' : 'Create';
			}
			$form->add( 'save', SubmitType::class, [ 'label' => $saveLabel ] );
		}

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$stepModel = new StepModel( $step );
			$stepModel->persist( $entityManager, true );
		}

		return $form;
	}
}
