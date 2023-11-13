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
	public function renderSystemIndex( Request $request ): Response
	{
		return $this->render( 'admin/system/index.html.twig', [
			'title'       => 'System',
			'cards'       => [
				'environment' => [
					'icon'   => 'safe',
					'header' => $this->trans( 'Environment' ),
					'body'   => $this->trans( 'Configure environment setup.' ),
					'link'   => $this->generateUrl( 'system_env' ),
				],
				'import' => [
					'icon'   => 'download',
					'header' => $this->trans( 'Import' ),
					'body'   => $this->trans( 'Import JSON configs.' ),
					'link'   => $this->generateUrl( 'import_entities' ),
				],
			],
			'breadcrumbs' => [
				[
					'title'   => $this->trans( 'System' ),
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/import', name: 'import_entities' )]
	public function renderImport( Request $request, ModelImporter $importer )
	{
		// @todo React component using react-diff-viewer.

		$form = $this->createFormBuilder( [] )->add( 'data', TextareaType::class, [
			'label' => $this->trans( 'JSON data' ),
			'attr'  => [ 'rows' => 15 ],
		] )->add( 'submit', SubmitType::class, [ 'label' => $this->trans( 'Import' ) ] )->getForm();

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
			'header'      => $this->trans( 'Import' ),
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'system_index' ),
					'title' => $this->trans( 'System' ),
				],
				[
					'title'   => $this->trans( 'Import' ),
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/system/env', name: 'system_env' )]
	public function renderSystemEnv( Request $request, System $system ): Response
	{
		return $this->render( 'admin/system/index.html.twig', [
			'backlink'    => $this->generateUrl( 'system_index' ),
			'header'      => $this->trans( 'Environment' ),
			'form'        => $this->formEnv( $request, $system->getEnv() ),
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'system_index' ),
					'title' => $this->trans( 'System' ),
				],
				[
					'title'   => $this->trans( 'Environment' ),
					'current' => true,
				],
			],
		] );
	}

	public function formEnv( Request $request, Env $env, false|string $saveLabel = '' ): FormInterface
	{
		$form = $this->createForm( EnvironmentFormType::class );

		if ( false !== $saveLabel ) {
			$form->add( 'save', SubmitType::class, [ 'label' => $saveLabel ?? $this->trans( 'Save' ) ] );
		}

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			foreach ( $form->getData() as $key => $value ) {

				switch ( $key ) {
					case 'APP_SECRET':
						if ( ! $value ) {
							// @todo Move this?
							$value = $env->get( $key );
							if ( ! $value ) {
								$value = bin2hex( random_bytes( 20 ) );
							}
						}
					break;
				}

				$env->set( $key, $value );
			}
			$env->persist();
		} else {
			$form->setData( $env->fetch() );
		}

		return $form;
	}
}
