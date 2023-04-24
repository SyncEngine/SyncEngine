<?php

namespace App\Controller;

use App\Entity\Flow;
use App\Entity\Step;
use App\Entity\StepOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StepOrderController extends AbstractController
{
	#[Route('/steporder/view/{id}', name: 'view_steporder')]
	public function viewStepOrder(Flow $flow, Request $request, EntityManagerInterface $entityManager): Response
	{
		$step = new Step();
		$form = $this->createFormBuilder()
			->add('name', TextType::class)
			->add('description', TextType::class)
			->add('config', TextareaType::class)
			->getForm();

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$frm = $form->getData();

			$step->setName($frm['name']);
			$step->setDescription($frm['description']);
			$step->setConfig(explode(',', $frm['config']));
			$entityManager->persist($step);

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
			$datafields = $stepController->ExecuteStep($stp->getStep(), $datafields);
		}
		return $datafields;

	}

}
