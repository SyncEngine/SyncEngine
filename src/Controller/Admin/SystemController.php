<?php

namespace SyncEngine\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Attribute\MenuItem;
use SyncEngine\Form\EnvironmentFormType;
use SyncEngine\Service\Env;
use SyncEngine\Service\ModelImporter;
use SyncEngine\Service\System;

class SystemController extends AdminController
{
	#[Route( '/system', name: 'system_index' )]
	#[MenuItem( menu: 'main', route: 'syncengine_system_index', label: 'System', icon: 'bi bi-gear-wide-connected' )]
	public function renderSystemIndex( Request $request ): Response
	{
		return $this->render(
			'admin/system/index.html.twig',
			[
				'header'      => $this->trans( 'System' ),
				'icon'        => 'gear-wide-connected',
				'cards'       => [
					'environment' => [
						'icon'   => 'motherboard',
						'header' => $this->trans( 'Environment' ),
						'body'   => $this->trans( 'Configure environment setup' ),
						'link'   => $this->generateUrl( 'syncengine_system_env' ),
					],
					'vault'       => [
						'icon'   => 'safe',
						'header' => $this->trans( 'Vault' ),
						'body'   => $this->trans( 'Configure vault secrets' ),
						'link'   => $this->generateUrl( 'syncengine_system_vault' ),
					],
					'processes'   => [
						'icon'   => 'terminal',
						'header' => $this->trans( 'Processes' ),
						'body'   => $this->trans( 'Manage system processes' ),
						'link'   => $this->generateUrl( 'syncengine_system_processes' ),
					],
					'import'      => [
						'icon'   => 'download',
						'header' => $this->trans( 'Import' ),
						'body'   => $this->trans( 'Import JSON configs' ),
						'link'   => $this->generateUrl( 'syncengine_import_entities' ),
					],
					'info'      => [
						'icon'   => 'info-square',
						'header' => $this->trans( 'Info' ),
						'body'   => $this->trans( 'System information' ),
						'link'   => $this->generateUrl( 'syncengine_system_info' ),
					],
				],
				'breadcrumbs' => [
					[
						'title'   => $this->trans( 'System' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/import', name: 'import_entities' )]
	public function renderImport( Request $request, ModelImporter $importer )
	{
		// @todo React component using react-diff-viewer.

		$form = $this->createFormBuilder( [] )->add(
			'data',
			TextareaType::class,
			[
				'label' => $this->trans( 'JSON data' ),
				'attr'  => [ 'rows' => 15 ],
			]
		)->add( 'submit', SubmitType::class, [ 'label' => $this->trans( 'Import' ) ] )->getForm();

		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$data = $form['data']->getData();

			if ( $data ) {
				$data = json_decode( $data, true );
				if ( ! is_array( $data ) ) {
					$this->addFlash( 'warning', $this->trans( 'Format must be JSON.' ) );

					return $this->redirectToRoute( 'syncengine_import_entities' );
				}
				$importer->import( $data );
			}

			$this->addFlash( 'success', $this->trans( 'Import successful' ) );
		}

		return $this->render(
			'admin/import.html.twig',
			[
				'backlink'    => true,
				'header'      => $this->trans( 'Import' ),
				'icon'        => 'download',
				'form'        => $form,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_system_index' ),
						'title' => $this->trans( 'System' ),
					],
					[
						'title'   => $this->trans( 'Import' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/system/info', name: 'system_info' )]
	public function renderSystemInfo( Connection $connection, Request $request ): Response
	{
		$info = [
			[
				'Name' => 'Server Host',
				'Value' => $_SERVER[ 'SERVER_NAME' ],
			],
			[
				'Name' => 'Server Version',
				'Value' => $_SERVER[ 'SERVER_SOFTWARE' ],
			],
			[
				'Name' => 'PHP Version',
				'Value' => PHP_VERSION,
			],
			[
				'Name' => 'PHP Memory Limit',
				'Value' => ini_get('memory_limit'),
			],
			[
				'Name' => 'PHP Max Execution time',
				'Value' => ini_get( 'max_execution_time' ),
			],
			[
				'Name' => 'PHP Max Upload Size',
				'Value' => ini_get('upload_max_filesize'),
			]
		];

		if ( $connection->isConnected() ) {
			$info[] = [
				'Name' => 'Database Driver',
				'Value' => $connection->getDriver()->getDatabasePlatform()->getName(),
			];
			$info[] = [
				'Name' => 'Database Version',
				'Value' => $connection->executeQuery( 'SELECT @@version' )->fetchOne(),
			];
			$info[] = [
				'Name' => 'Database Name',
				'Value' => $connection->getDatabase(),
			];
		}

		$html = $this->renderView( 'admin/_partials/table.html.twig', [
			'items' => $info
		] );

		return $this->render(
			'admin/system/index.html.twig',
			[
				'backlink'    => $this->generateUrl( 'syncengine_system_index' ),
				'header'      => $this->trans( 'Info' ),
				'icon'        => 'info-square',
				'html'        => $html,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_system_index' ),
						'title' => $this->trans( 'System' ),
					],
					[
						'title'   => $this->trans( 'Info' ),
						'current' => true,
					],
				],
			]
		);
	}

	#[Route( '/system/env', name: 'system_env' )]
	public function renderSystemEnv( Request $request, System $system ): Response
	{
		return $this->render(
			'admin/system/index.html.twig',
			[
				'backlink'    => $this->generateUrl( 'syncengine_system_index' ),
				'header'      => $this->trans( 'Environment' ),
				'icon'        => 'motherboard',
				'form'        => $this->formEnv( $request, $system->getEnv() ),
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_system_index' ),
						'title' => $this->trans( 'System' ),
					],
					[
						'title'   => $this->trans( 'Environment' ),
						'current' => true,
					],
				],
			]
		);
	}

	public function formEnv( Request $request, Env $env, false|string $saveLabel = '' ): FormInterface
	{
		$form = $this->createForm( EnvironmentFormType::class );

		if ( false !== $saveLabel ) {
			$form->add( 'save', SubmitType::class, [ 'label' => $saveLabel ?? $this->trans( 'Save' ) ] );
		}

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$data = $form->getData();
			$_env = $data['APP_ENV'] ?? $env->get( 'APP_ENV' );

			if ( 'dev' === $_env ) {
				// Revert to default.
				$env->unset( 'APP_DEBUG' );
				unset( $data['APP_DEBUG'] );
			}

			foreach ( $data as $key => $value ) {

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
					case 'DATABASE_URL':
					case 'MAILER_DSN':
						if ( empty( $value ) ) {
							$env->unset( $key );
							continue 2; // Do not override default.
						}
					break;
					case 'APP_DEBUG':
					break;
				}

				$env->set( $key, $value );
			}
			$env->persist();
		} else {
			$formData = $env->fetch();
			$formData['APP_DEBUG'] = $env->get( 'APP_DEBUG' ) ?: ( 'prod' !== $env->get( 'APP_ENV' ) );
			$form->setData( $formData );
		}

		return $form;
	}
}
