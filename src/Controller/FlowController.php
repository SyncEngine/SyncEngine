<?php

namespace App\Controller;

use App\Entity\Flow;
use App\Entity\Step;
use App\Form\FlowFormType;
use App\Form\StepFormType;
use App\Form\Type\JsonType;
use App\Service\StepService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
			$steps[] = $entityManager->getRepository(Step::class)->findOneBy( ['id' => $stepID] );
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
		$steps = [];
		foreach ( StepService::getSteps() as $step ) {
			$steps[ $step->getId() ] = [
				'id'          => $step->getId(),
				'name'        => $step->getName(),
				'description' => $step->getDescription(),
				'config'      => $step->getConfig(),
			];
		}

		$form = $this->createFormBuilder()
			->add( 'steps', JsonType::class, [
				'data' => $flow->getSteps(),
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
				'attr' => [
					'data-controller' => 'config',
					'data-type'       => 'flow',
					'data-args'       => json_encode( [
						'order'    => $flow->getSteps(),
						'steps'    => $steps,
						'endpoint' => 'step/json',
					] ),
				]
			] )
			->add('save', SubmitType::class, ['label' => 'Save order'])
			->getForm();

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$formData = $form->getData();

			$steps = [];
			foreach ( $formData['steps'] as $stepID ) {
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

	/**
	 * @todo Fully refactor.
	 */
	#[Route('/step/unlink/{step}-{flow}', name: 'unlink_step')]
	public function unlinkStep(Step $step,Flow $flow, Request $request, EntityManagerInterface $entityManager): Response
	{
		$flows = $entityManager->getRepository(Flow::class)->findAll();
		$delete = 1;
		foreach ($flows as $flw){
			if ($flw != $flow and in_array($step->getId(), $flw->getSteps()))
			{
				$this->addFlash('warning', 'Step is also found in flow: '.$flw->getName());
				$delete = 0;
			}
		}

		$flow->setSteps(array_diff( $flow->getSteps(), [$step->getId()]));
		$entityManager->persist($flow);
		$entityManager->flush();
		$this->addFlash('success', 'Succesfully unlinked step from flow: '.$flow->getName());

		if ($delete) {
			$entityManager->remove($step);
			$entityManager->flush();
			$this->addFlash('success', 'Succesfully deleted step');
		}

		$route = $request->headers->get('referer');
		return $this->redirect($route);
	}
}
