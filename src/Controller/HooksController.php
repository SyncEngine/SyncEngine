<?php

namespace SyncEngine\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\EventDispatcher\Event\RenderHookEvent;

class HooksController extends DefaultController
{
	public function __construct(
		private EventDispatcherInterface $dispatcher
	) {}

	#[Route( '/json/hook/{hookName}', name: 'json_hook' )]
	public function jsonHook( string $hookName ): JsonResponse
	{
		try {
			$html = $this->doHook( $hookName );
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

	public function getThemeHooks( $hookNames )
	{
		return new JsonResponse( $this->dohooks( (array) $hookNames ) );
	}

	public function renderHook( string $hookName ): Response
	{
		return new Response( $this->doHook( $hookName ) );
	}

	public function dohooks( array $hookNames ): array
	{
		$return = [];

		foreach ( $hookNames as $hookName ) {
			$return[ $hookName ] = $this->doHook( $hookName );
		}

		return $return;
	}

	public function doHook( string $hookName ): string
	{
		$event = new RenderHookEvent( '' );

		$this->dispatcher->dispatch( $event, 'syncengine.hook.render.' . $hookName );

		return $event->getHtml();
	}
}
