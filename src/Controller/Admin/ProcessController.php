<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Form\ProcessManagerFormType;
use SyncEngine\Messenger\MessengerManager;
use SyncEngine\Service\Conditions;
use SyncEngine\Service\Env;

class ProcessController extends DefaultController
{
	#[Route('system/processes', name: 'system_processes' )]
	function renderIndex( Request $request, MessengerManager $manager, Env $env ): Response
	{
		$this->denyAccessUnlessGranted('ROLE_ADMIN');

		$form = [
			'icon'   => 'system-processes-manager',
			'header' => $this->trans( 'Configuration' ),
			'body'   => $this->trans('Configure manager to optimize SyncEngine for your server.'),
			'form'   => $this->formProcessManager( $request, $env )->createView(),
		];

		$validate = $manager->validate();
		if ( true !== $validate ) {
			$this->addFlash( 'warning', $validate );
		}

		$card = [
			'icon'   => 'system-processes-config',
			'header' => $this->trans( 'Manager' ),
		];

		if ( $manager->isInternal() ) {
			$card['body'] = $this->trans('Auto-starts background async workers to handle automations.');
			if ( $manager->isEnabled() ) {
				$card['link'] = [
					'url'     => $this->generateUrl( 'syncengine_system_disable_manager' ),
					'text'    => $this->trans( 'Disable' ),
					'variant' => 'outline-warning',
				];
				$card['badge'] = [
					'text'    => $this->trans( 'Enabled' ),
					'variant' => 'success',
				];
			} else {
				if ( true === $validate ) {
					$card['link'] = [
						'url'     => $this->generateUrl( 'syncengine_system_enable_manager' ),
						'text'    => $this->trans( 'Enable' ),
						'variant' => 'outline-success',
					];
				} else {
					$card['link'] = [
						'url'     => false,
						'text'    => $this->trans( 'Cannot enable manager' ),
						'variant' => 'outline-success',
					];
				}
				$card['badge'] = [
					'text'    => $this->trans( 'Disabled' ),
					'variant' => 'warning',
				];
			}
		} elseif ( $manager->isCron() ) {
			$card['badge'] = [
				'text'    => 'Cron',
				'variant' => 'info',
			];

			$card['list'][] = $this->trans('The manager is controlled by a cronjob.');
			$form['list'] = [
				'Cron command: `php bin/console syncengine:messenger:manager:cron`',
			];
		} else {
			$card['badge'] = [
				'text'    => $this->trans('External'),
				'variant' => 'info',
			];

			$card['list'][] = $this->trans('The manager is controlled externally.');
		}

		$card['list'][] = [
			'text' => $this->trans('Queue size:'),
			'badge' => $manager->getQueueCount( 'async' ),
		];
		$card['list'][] = [
			'text' => $this->trans('Active workers:'),
			'badge' => $manager->getWorkerCount( 'async' ),
		];

		foreach ( $manager->getWorkerProcesses() as $pid => $workerProcess ) {
			$card['list'][] = $this->trans('Worker ID #{pid} | Created: {timestamp} | Ping: {ping}', ['pid'=>$pid, 'timestamp'=>date( 'Y-m-d H:i:s', $workerProcess['timestamp'] ),'ping'=>( time() - $manager->getWorkerPing( $pid ) )]);
		}

		return $this->render( 'admin/system/index.html.twig', [
			'backlink'    => $this->generateUrl( 'syncengine_system_index' ),
			'header'      => $this->trans( 'Processes' ),
			'icon'        => 'system-processes',
			'cards'       => [
				'manager' => $card,
				'config'  => $form,
			],
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'syncengine_system_index' ),
					'title' => $this->trans( 'System' ),
				],
				[
					'title'   => $this->trans( 'Processes' ),
					'current' => true,
				],
			],
		] );
	}

	#[Route('system/processes/enable', name: 'system_enable_manager' )]
	function handleEnable( Request $request, MessengerManager $manager ): Response
	{
		$this->denyAccessUnlessGranted('ROLE_ADMIN');

		$manager->enable();

		return $this->redirectToRoute( 'syncengine_system_processes' );
	}

	#[Route('system/processes/disable', name: 'system_disable_manager' )]
	function handleDisable( Request $request, MessengerManager $manager ): Response
	{
		$this->denyAccessUnlessGranted('ROLE_ADMIN');

		$manager->disable();

		return $this->redirectToRoute( 'syncengine_system_processes' );
	}

	public function formProcessManager( Request $request, Env $env, false|string $saveLabel = '' ): FormInterface
	{
		$form = $this->createForm( ProcessManagerFormType::class );

		if ( false !== $saveLabel ) {
			$form->add( 'save', SubmitType::class, [ 'label' => $saveLabel ?? $this->trans( 'Save' ) ] );
		}

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {

			$data = $form->getData();
			$manager = $data['SYNCENGINE_MESSENGER_MANAGER'] ?? null;

			if ( ! $manager ) {
				$this->addFlash( 'warning', $this->trans( 'Invalid Manager' ) );
			} else {

				if ( MessengerManager::MANAGER_INTERNAL === $manager ) {
					foreach ( $form->getData() as $key => $value ) {
						if ( ! Conditions::isEmptyValue( $value ) ) {
							$env->set( $key, $value );
						} else {
							$env->unset( $key );
						}
					}
				} else {
					$env->set( 'SYNCENGINE_MESSENGER_MANAGER', $manager );
				}

				$env->persist();

				var_dump( $env->get() );die;

				$this->addFlash( 'success', $this->trans( 'Configuration saved' ) );

				$this->redirectToRoute( 'syncengine_system_processes' );
			}

		} else {
			$form->setData( $env->fetch() );
		}

		return $form;
	}
}
