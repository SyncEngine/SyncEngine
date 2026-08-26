<?php

namespace SyncEngine\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
		#[Autowire('%syncengine.security.csrf_cookie_name%')]
		private string $cookieName,
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
		$path = $request->getPathInfo();

		if ( ! str_contains( $path, '/json/' ) && ! str_ends_with( $path, '/json' ) ) {
			return;
		}

		$token = $request->cookies->get( $this->cookieName );

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
