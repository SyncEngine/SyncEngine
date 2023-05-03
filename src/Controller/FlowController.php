<?php

namespace App\Controller;

use App\Entity\Flow;
use App\Entity\Step;
use App\Entity\StepOrder;
use App\Form\FlowFormType;
use App\Form\StepFormType;
use Doctrine\ORM\EntityManagerInterface;
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

		$form->handleRequest($request);
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist( $flow );
			$entityManager->flush();
			$this->addFlash('success', 'Succesfully added!');

			return $this->redirectToRoute('app_index');
		}

		return $this->render('flow/create.html.twig', [
			'form' => $form,
		]);
	}

	#[Route('/flow/edit/{id}', name: 'edit_flow')]
	public function edit(Flow $flow, Request $request, EntityManagerInterface $entityManager): Response
	{
		$form = $this->createForm( FlowFormType::class, $flow );

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist( $flow );
			$entityManager->flush();
			$this->addFlash( 'success', 'Succesfully edited!' );

			return $this->redirectToRoute('app_index');
		}

		return $this->render('flow/edit.html.twig', [
			'form' => $form,
		]);
	}

	#[Route('/steporder/view/{id}', name: 'view_steporder')]
	public function viewStepOrder(Flow $flow, Request $request, EntityManagerInterface $entityManager): Response
	{
		$step = new Step();
		$form = $this->createForm( StepFormType::class, $step );

		$form->handleRequest($request);
		if ( $form->isSubmitted() && $form->isValid() ) {

			$entityManager->persist( $step );

			// Save step order.
			$steporder = new StepOrder();
			$steporder->setFlow($flow);
			$steporder->setStep($step);
			$previousPositionStep = $entityManager->getRepository(StepOrder::class)->findBy(['flow' => $flow], ['position' => 'DESC'], 1, 0);
			if ($previousPositionStep) {
				$steporder->setPosition($previousPositionStep[0]->getPosition() + 1);
			} else {
				$steporder->setPosition(1);
			}
			$entityManager->persist($steporder);

			$entityManager->flush();

			$this->addFlash('success', 'Succesfully added!');
			return $this->redirectToRoute('view_steporder', ['id' => $flow->getId()]);
		}
		return $this->render('step_order/view.html.twig', [
			'form' => $form,
			'steps' => $flow->getStepOrders()
		]);
	}

	public function ExecuteStepOrders($stepOrders, $datafields)
	{
		//@todo order by position of step orders
		$stepController = new StepController();
		foreach ($stepOrders as $stp){
			$datafields = $stepController->executeStep($stp->getStep(), $datafields);
		}
		return $datafields;

	}
}
