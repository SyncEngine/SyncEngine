<?php

namespace App\Controller;

use App\Entity\Step;
use App\Form\StepFormType;
use App\Service\StepService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StepController extends EntityController
{
	#[Route('/step/json','json_step')]
	public function handleJson( Request $request, EntityManagerInterface $entityManager ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$action = $request->request->get( 'action' );
		$step = ( $id && is_numeric( $id ) ) ? StepService::getStep( $id )->getEntity() : new Step();
		$json = [];

		switch ( $action ) {
			case 'delete':
				// @todo
			break;
			case 'form':
			case 'create':
			case 'edit':
				$form = $this->form( $step, $request, $entityManager, false );

				if ( $form->isSubmitted() ) {
					$json['success'] = $form->isValid();
				}

				$json['step'] = $step;
				$json['html'] = $this->render( '_partials/form.html.twig', [
					'form' => $form,
				] );
			break;
			default:
				$json = $step;
				break;
		}

		return $this->json( $this->removeCircularReference( $json ) );
	}

	#[Route('/step/create', name: 'create_step')]
	public function create( Request $request, EntityManagerInterface $entityManager ): Response
	{
		$step = new Step();
		$form = $this->form( $step, $request, $entityManager );
		if ($form->isSubmitted() && $form->isValid()) {
			$this->addFlash('success', 'Successfully created step!');
			return $this->redirectToRoute('app_index');
		}

		return $this->render('step/create.html.twig', [
			'form' => $form,
		]);
	}

	#[Route('/step/edit/{id}', name: 'edit_step')]
	public function edit(Step $step, Request $request, EntityManagerInterface $entityManager): Response
	{
		$form = $this->form( $step, $request, $entityManager );
		if ($form->isSubmitted() && $form->isValid()) {
			$this->addFlash('success', 'Successfully edited step!');
			return $this->redirectToRoute('app_index');
		}

		return $this->render('step/edit.html.twig', [
			'form' => $form,
		]);
	}

	public function form( Step $step, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		$form = $this->createForm(StepFormType::class, $step);
		if ( false !== $saveLabel ) {
			if ( ! $saveLabel ) {
				$saveLabel = ( $step->getId() ) ? 'Update' : 'Create';
			}
			$form->add('save', SubmitType::class, ['label' => $saveLabel]);
		}

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {

			$entityManager->persist($step);
			$entityManager->flush();
		}

		return $form;
	}
}
