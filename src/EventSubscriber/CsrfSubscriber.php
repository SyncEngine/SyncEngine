<?php

namespace SyncEngine\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\SameOriginCsrfTokenManager;

final class CsrfSubscriber implements EventSubscriberInterface
{
	public function __construct(
		private readonly CsrfTokenManagerInterface $csrfTokenManager,
	) {}

	public static function getSubscribedEvents(): array
	{
		return [
			KernelEvents::REQUEST => 'onRequest',
		];
	}

	public function onRequest( RequestEvent $event ): void
	{
		if ( ! $event->isMainRequest() ) {
			return;
		}

		$request = $event->getRequest();

		if ( ! str_contains( $request->getPathInfo(), '/json/' )
		     || ! in_array( $request->getMethod(), [ 'POST', 'PUT', 'PATCH', 'DELETE' ], true ) ) {
			return;
		}

		$token = $request->cookies->get( 'csrf-token' );

		if ( ! $token ) {
			$event->setResponse(
				new JsonResponse(
					[ 'success' => false, 'error' => 'CSRF token missing' ], 403,
				)
			);

			return;
		}

		$valid = $this->csrfTokenManager->isTokenValid(
			new CsrfToken( 'submit', $token )
		);

		if ( ! $valid ) {
			$event->setResponse(
				new JsonResponse(
					[ 'success' => false, 'error' => 'Invalid CSRF token' ], 403,
				)
			);
		}
	}
}
