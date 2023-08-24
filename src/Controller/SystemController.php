<?php

namespace App\Controller;

use App\Form\EnvironmentFormType;
use App\Service\Env;
use App\Service\ModelImporter;
use App\Service\System;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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

	#[Route( '/import', name: 'import_entities' )]
	public function import( Request $request, ModelImporter $importer, TranslatorInterface $translator )
	{
		// @todo React component using react-diff-viewer.

		$form = $this->createFormBuilder( [] )->add( 'data', TextareaType::class, [
			'label' => 'JSON data',
			'attr'  => [ 'rows' => 15 ],
		] )->add( 'submit', SubmitType::class, [ 'label' => 'Import' ] )->getForm();

		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$data = $form['data']->getData();

			if ( $data ) {
				$data = json_decode( $data, true );
				$importer->import( $data );
			}

			return $this->redirectToRoute( 'app_index' );
		}

		return $this->render( 'admin/import.html.twig', [
			'backlink'    => true,
			'header'      => $translator->trans( 'Import' ),
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'system_index' ),
					'title' => $translator->trans( 'System' ),
				],
				[
					'title'   => $translator->trans( 'Import' ),
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/system/env', name: 'system_env' )]
	public function system_env( Request $request, TranslatorInterface $translator, System $system ): Response
	{
		return $this->render( 'admin/system/index.html.twig', [
			'backlink'    => $this->generateUrl( 'system_index' ),
			'header'      => $translator->trans( 'Environment' ),
			'form'        => $this->formEnv( $request, $system->getEnv() ),
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

	public function formEnv( Request $request, Env $env, $saveLabel = '' ): FormInterface
	{
		$form = $this->createForm( EnvironmentFormType::class );

		if ( false !== $saveLabel ) {
			$form->add( 'save', SubmitType::class, [ 'label' => $saveLabel ?? 'Save' ] );
		}

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
