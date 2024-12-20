<?php

namespace SyncEngine\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\EventDispatcher\Event\HookEvent;
use SyncEngine\EventDispatcher\Event\RenderHookEvent;

class HooksController extends DefaultController
{
	public function __construct(
		private readonly EventDispatcherInterface $dispatcher
	) {}

	#[Route( '/json/hook/{hookName:hookName}', name: 'json_hook' )]
	public function jsonHook( string $hookName ): JsonResponse
	{
		try {
			$html = $this->renderHookHtml( $hookName );
			return $this->json( [
				'success' => true,
				'html' => $html,
			] );
		} catch ( \Exception $e ) {
			return $this->json( [
				'success' => false,
				'message' => $e->getMessage(),
			] );
		}
	}

	public function getThemeHooks( $hookNames ): Response
	{
		return new JsonResponse( $this->renderHooksHtml( (array) $hookNames ) );
	}

	public function renderHook( string $hookName ): Response
	{
		return new Response( $this->renderHookHtml( $hookName ) );
	}

	public function renderHooksHtml( array $hookNames ): array
	{
		$return = [];

		foreach ( $hookNames as $hookName ) {
			$return[ $hookName ] = $this->renderHookHtml( $hookName );
		}

		return $return;
	}

	public function renderHookHtml( string $hookName ): string
	{
		$event = $this->doHook( new RenderHookEvent( '' ), $hookName );

		return $event->getHtml();
	}

	public function doHook( HookEvent $event, string $hookName ): HookEvent
	{
		$this->dispatcher->dispatch( $event, 'syncengine.hook.' . $event->getHookType() . '.' . $hookName );
		return $event;
	}
}
