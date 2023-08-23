<?php

namespace App\Controller;

use App\Form\EnvironmentFormType;
use App\Service\Env;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
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
			'title'       => 'System',
			'cards'       => [
				'environment' => [
					'icon'   => 'safe',
					'header' => $translator->trans( 'Environment' ),
					'body'   => 'Configure environment variables here.',
					'link'   => $this->generateUrl( 'system_env' ),
				],
				'import' => [
					'icon'   => 'download',
					'header' => $translator->trans( 'Import' ),
					'body'   => 'Import JSON configs.',
					'link'   => $this->generateUrl( 'import_entities' ),
				],
			],
			'breadcrumbs' => [
				[
					'title'   => $translator->trans( 'System' ),
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/system/env', name: 'system_env' )]
	public function system_env( Request $request, TranslatorInterface $translator, Env $env ): Response
	{
		return $this->render( 'admin/system/index.html.twig', [
			'backlink'    => $this->generateUrl( 'system_index' ),
			'header'      => $translator->trans( 'Environment' ),
			'form'        => $this->formEnv( $request, $env ),
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'system_index' ),
					'title' => 'System',
				],
				[
					'title'   => $translator->trans( 'Environment' ),
					'current' => true,
				],
			],
		] );
	}

	public function formEnv( Request $request, Env $env ): FormInterface
	{
		$env->setType( 'local' );

		$form = $this->createForm( EnvironmentFormType::class )
		             ->add( 'save', SubmitType::class, [ 'label' => 'Save' ] );

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			foreach ( $form->getData() as $key => $value ) {
				$env->set( $key, $value );
			}
			$env->persist();
		} else {
			$form->setData( $env->fetch() );
		}

		return $form;
	}
}
