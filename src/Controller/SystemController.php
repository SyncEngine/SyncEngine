<?php

namespace App\Controller;

use App\Form\EnvironmentFormType;
use App\Service\Env;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class SystemController extends AdminController
{
	#[Route( '/system', name: 'system_index' )]
	public function system_index( Request $request, TranslatorInterface $translator ): Response
	{
		return $this->render( 'admin/system/index.html.twig', [
			'title' => 'System',
			'cards' => [
				'environment' => [
					'header' => 'Environment',
					'body' => 'Configure environment variables here.',
					'link' => $this->generateUrl( 'system_env' ),
				],
			],
			'breadcrumbs' => [
				[
					'title' => 'System',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/system/env', name: 'system_env' )]
	public function system_env( Request $request, TranslatorInterface $translator, Env $env ): Response
	{
		$env->setType( 'local' );

		$form = $this->createForm( EnvironmentFormType::class )->add(
			'save', SubmitType::class, [ 'label' => 'Save' ]
		);


		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			foreach ( $form->getData() as $key => $value ) {
				$env->set( $key, $value );
			}
			$env->persist();
		} else {
			$form->setData( $env->fetch() );
		}

		return $this->render( 'admin/system/index.html.twig', [
			'backlink' => $this->generateUrl( 'system_index' ),
			'header' => 'Environment',
			'form' => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'system_index' ),
					'title' => 'System',
				],
				[
					'title' => 'Environment',
					'current' => true,
				],
			],
		] );
	}
}
