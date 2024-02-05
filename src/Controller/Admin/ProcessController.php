<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Form\ProcessManagerFormType;
use SyncEngine\Messenger\MessengerManager;
use SyncEngine\Service\Env;

class ProcessController extends DefaultController
{
	#[Route('system/processes', name: 'system_processes' )]
	function renderIndex( Request $request, MessengerManager $manager, Env $env ): Response
	{
		$this->denyAccessUnlessGranted('ROLE_ADMIN');

		$form = [
			'icon'   => 'cpu-fill',
			'header' => $this->trans( 'Configuration' ),
			'body'   => 'Configure manager to optimize SyncEngine for your server.',
			'form'   => $this->formProcessManager( $request, $env )->createView(),
		];

		$validate = $manager->validate();
		if ( true !== $validate ) {
			$this->addFlash( 'warning', $validate );
		}

		$card = [
			'icon'   => 'cpu',
			'header' => $this->trans( 'Manager' ),
			'body'   => 'Auto-starts background async workers to handle automations.',
		];

		if ( ! $manager->isInternal() ) {
			if ( $manager->isCron() ) {
				$card['badge'] = [
					'text'    => 'Cron',
					'variant' => 'info',
				];
			} else {
				$card['badge'] = [
					'text'    => 'External',
					'variant' => 'info',
				];
			}

			$card['list'] = [
				'Queue size: ' . $manager->getQueueCount( 'async' ),
			];
		} else {
			if ( $manager->isEnabled() ) {
				$card['link'] = [
					'url'     => $this->generateUrl( 'system_disable_manager' ),
					'text'    => $this->trans( 'Disable' ),
					'variant' => 'outline-warning',
				];
				$card['badge'] = [
					'text'    => $this->trans( 'Enabled' ),
					'variant' => 'success',
				];
			} else {
				$card['link'] = [
					'url'     => $this->generateUrl( 'system_enable_manager' ),
					'text'    => $this->trans( 'Enable' ),
					'variant' => 'outline-success',
				];
				$card['badge'] = [
					'text'    => $this->trans( 'Disabled' ),
					'variant' => 'warning',
				];
			}

			$card['list'] = [
				'Active workers: ' . $manager->getWorkers( 'async' ),
				'Queue size: ' . $manager->getQueueCount( 'async' ),
			];
		}

		return $this->render( 'admin/system/index.html.twig', [
			'backlink'    => $this->generateUrl( 'system_index' ),
			'header'      => $this->trans( 'Processes' ),
			'icon'        => 'terminal',
			'cards'       => [
				'manager' => $card,
				'config'  => $form,
			],
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'system_index' ),
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

		return $this->redirectToRoute( 'system_processes' );
	}

	#[Route('system/processes/disable', name: 'system_disable_manager' )]
	function handleDisable( Request $request, MessengerManager $manager ): Response
	{
		$this->denyAccessUnlessGranted('ROLE_ADMIN');

		$manager->disable();

		return $this->redirectToRoute( 'system_processes' );
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

				if ( 'internal' === $manager ) {
					foreach ( $form->getData() as $key => $value ) {
						if ( ! empty( $value ) ) {
							$env->set( $key, $value );
						}
					}
				} else {
					$env->set( 'SYNCENGINE_MESSENGER_MANAGER', $manager );
				}

				$env->persist();

				$this->redirectToRoute( 'system_processes' );
			}

		} else {
			$form->setData( $env->fetch() );
		}

		return $form;
	}
}
