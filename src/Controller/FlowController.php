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
		$steps = [];
		foreach ( StepService::getSteps() as $step ) {
			$steps[ $step->getId() ] = [
				'id'          => $step->getId(),
				'name'        => $step->getName(),
				'description' => $step->getDescription(),
				'config'      => $step->getConfig(),
			];
		}

		$form = $this->createForm( FlowFormType::class, $flow );
		$form->add( 'steps', JsonType::class, [
				'data' => $flow->getSteps(),
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'attr' => [
					'data-controller' => 'config',
					'data-type'       => 'flow',
					'data-args'       => json_encode( [
						'order'    => $flow->getSteps(),
						'steps'    => $steps,
						// @todo Move all endpoints to a global var.
						'endpoint' => $this->generateUrl( 'json_step' )
					] ),
				]
			] );
		$form->add('save', SubmitType::class, ['label' => 'Update']);

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

			$this->addFlash( 'success', 'Successfully edited!' );

			return $this->redirectToRoute( 'edit_flow', [ 'id' => $flow->getId() ] );
		}

		return $this->render( 'flow/edit.html.twig', [
			'flow' => $flow,
			'form' => $form,
		] );
	}
}
