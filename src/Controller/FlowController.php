<?php

namespace App\Controller;

use App\Entity\Flow;
use App\Entity\Step;
use App\Form\FlowFormType;
use App\Form\StepFormType;
use App\Form\Type\JsonType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class FlowController extends DefaultController
{
	#[Route('/flow/create', name: 'create_flow')]
	public function create(Request $request, EntityManagerInterface $entityManager): Response
	{
		$flow = new Flow();

		$form = $this->createForm( FlowFormType::class, $flow );
		$form->add('save', SubmitType::class, ['label' => 'Create']);

		$form->handleRequest($request);
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist( $flow );
			$entityManager->flush();

			$this->addFlash('success', 'Successfully added!');

			return $this->redirectToRoute( 'edit_flow', [ 'id' => $flow->getId() ] );
		}

		return $this->render('flow/create.html.twig', [
			'form' => $form,
		]);
	}

	#[Route('/flow/edit/{id}', name: 'edit_flow')]
	public function edit( Flow $flow, Request $request, EntityManagerInterface $entityManager ): Response
	{
		$form = $this->createForm( FlowFormType::class, $flow );
		$form->add('save', SubmitType::class, ['label' => 'Update']);

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist( $flow );
			$entityManager->flush();

			$this->addFlash( 'success', 'Successfully edited!' );

			return $this->redirectToRoute( 'edit_flow', [ 'id' => $flow->getId() ] );
		}

		$formAddStep = $this->formAddStep( $flow, $request, $entityManager );
		if ( ! $formAddStep ) {
			return $this->redirectToRoute( 'edit_flow', [ 'id' => $flow->getId() ] );
		}

		$formCreateStep = $this->formCreateStep( $flow, $request, $entityManager );
		if ( ! $formCreateStep ) {
			return $this->redirectToRoute( 'edit_flow', [ 'id' => $flow->getId() ] );
		}

		$formSortSteps = $this->formSortSteps( $flow, $request, $entityManager );
		if ( ! $formSortSteps ) {
			return $this->redirectToRoute( 'edit_flow', [ 'id' => $flow->getId() ] );
		}

		$steps = [];
		foreach ( $flow->getSteps() as $stepID ) {
			array_push(
				$steps,
				$entityManager->getRepository(Step::class)->findOneBy( ['id' => $stepID] )
			);
		}

		return $this->render( 'flow/edit.html.twig', [
			'flow' => $flow,
			'steps' => $steps,
			'form' => $form,
			'formAddStep' => $formAddStep,
			'formCreateStep' => $formCreateStep,
			'formSortSteps' => $formSortSteps,
		] );
	}

	public function formAddStep( Flow $flow, Request $request, EntityManagerInterface $entityManager ): FormInterface|null
	{
		// Add existing step.
		$form = $this->createFormBuilder()
			->add('step', EntityType::class, [
					'class' => Step::class,
					'choice_label' => 'name',
					'row_attr' => [
						'class' => 'form-floating mb-2',
					],
				]
			)
			->add('save', SubmitType::class, ['label' => 'Add step'])
			->getForm();

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$currentSteps = $flow->getSteps();
			array_push($currentSteps, $form->getData()["step"]->getID());
			$flow->setSteps($currentSteps);

			$entityManager->persist($flow);
			$entityManager->flush();

			$this->addFlash('success', 'Successfully added step!');
			return null;
		}

		return $form;
	}

	public function formCreateStep( Flow $flow, Request $request, EntityManagerInterface $entityManager ): FormInterface|null
	{
		// Create new step.
		$newStep = new Step();

		$form = $this->createForm( StepFormType::class, $newStep );
		$form->add('save', SubmitType::class, ['label' => 'Create step']);

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist($newStep);
			$entityManager->flush();

			$currentSteps = $flow->getSteps();
			array_push($currentSteps, $newStep->getID() );
			$flow->setSteps($currentSteps);

			$entityManager->persist($flow);
			$entityManager->flush();

			$this->addFlash('success', 'Successfully created and added step!');
			return null;
		}

		return $form;
	}

	public function formSortSteps( Flow $flow, Request $request, EntityManagerInterface $entityManager ): FormInterface|null
	{
		$form = $this->createFormBuilder()
			->add( 'steps', JsonType::class, [
				'data' => $flow->getSteps(),
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
				'attr' => [
					'data-controller' => 'config',
					'data-type'       => 'sortable',
					'data-args'       => json_encode( [

					] ),
				]
			] )
			->add('save', SubmitType::class, ['label' => 'Save order'])
			->getForm();

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$formData = $form->getData();

			$steps = [];
			foreach ( $formData['steps'] as $index => $stepID ) {
				if ( $entityManager->getRepository( Step::class )->findOneBy( [ 'id' => $stepID ] ) ) {
					$steps[] = $stepID;
				}
			}

			$flow->setSteps( $steps );

			$entityManager->persist($flow);
			$entityManager->flush();

			$this->addFlash('success', 'Successfully sorted steps!');
			return null;
		}

		return $form;
	}

	public function executeFlow( $flow, $data )
	{
		$stepController = new StepController();
		foreach ( $flow->getSteps() as $stepID )
		{
			$data = $stepController->executeStep( $stepID, $data );
		}
		return $data;
	}
}
