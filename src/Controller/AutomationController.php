<?php

namespace App\Controller;

use App\Entity\Automation;
use App\Entity\Flow;
use App\Form\AutomationFormType;
use App\Service\AutomationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AutomationController extends EntityController
{
	#[Route('/automation/json','json_automation')]
	public function handleJson( Request $request, EntityManagerInterface $entityManager ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$action = $request->request->get( 'action' );
		$automation = ( $id && is_numeric( $id ) ) ? AutomationService::getAutomation( $id )->getEntity() : new Automation();
		$json = [];

		switch ( $action ) {
			case 'delete':
				// @todo
			break;
			case 'form':
			case 'create':
			case 'edit':
				$form = $this->form( $automation, $request, $entityManager, false );

				if ( $form->isSubmitted() ) {
					$json['success'] = $form->isValid();
				}

				$json['automation'] = $automation;
				$json['html'] = $this->render( '_partials/form.html.twig', [
					'form' => $form,
				] );
			break;
			default:
				$json = $automation;
			break;
		}

		return $this->json( $this->removeCircularReference( $json ) );
	}

	#[Route('/automation/create', name: 'create_automation')]
	public function create(Request $request, EntityManagerInterface $entityManager): Response
	{
		$automation = new Automation();
		$form = $this->form( $automation, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash('success', 'Successfully created automation!');
			return $this->redirectToRoute('app_index');
		}

		// @todo allow creating flows within the form.
		$flows = $entityManager->getRepository( Flow::class )->findAll();
		if( ! $flows ) {
			$this->addFlash('warning', 'You first need to create a flow');
			return $this->redirectToRoute('create_flow');
		}

		return $this->render('automation/create.html.twig', [
			'form' => $form,
		]);
	}

	#[Route('/automation/edit/{id}', name: 'edit_automation')]
	public function newAutomation(Automation $automation, Request $request, EntityManagerInterface $entityManager): Response
	{
		$form = $this->form( $automation, $request, $entityManager );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$this->addFlash('success', 'Successfully edited automation!');
			return $this->redirectToRoute('app_index');
		}

		return $this->render('automation/edit.html.twig', [
			'form' => $form,
		]);
	}

	public function form( Automation $automation, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		$form = $this->createForm(AutomationFormType::class, $automation);
		if ( false !== $saveLabel ) {
			if ( ! $saveLabel ) {
				$saveLabel = ( $automation->getId() ) ? 'Update' : 'Create';
			}
			$form->add('save', SubmitType::class, ['label' => $saveLabel]);
		}

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {

			$endpoint = $this->slugify($automation->getEndpoint());
			$automation->setEndpoint($endpoint);

			$entityManager->persist($automation);
			$entityManager->flush();
		}

		return $form;
	}
}
