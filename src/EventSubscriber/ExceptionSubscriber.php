<?php

namespace SyncEngine\EventSubscriber;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\RememberMe\RememberMeHandlerInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
	public function __construct(
		private readonly KernelInterface $kernel,
		private readonly RequestStack $requestStack,
		private readonly UrlGeneratorInterface $urlGenerator,
		private readonly TokenStorageInterface $tokenStorage,
		private readonly ?RememberMeHandlerInterface $rememberMeService = null
	) {}

	public static function getSubscribedEvents(): array
	{
		return [
			ExceptionEvent::class => [
				['processException', 10],
				//['logException', 0],
				//['notifyException', -10],
			],
		];
	}

	public function processException( ExceptionEvent $event ): void
	{
		if ( $this->kernel->isDebug()) {
			return;
		}

		$exception = $event->getThrowable();

		if ( $exception instanceof TableNotFoundException ) {
			$request = $this->requestStack->getCurrentRequest();
			$currentRoute = $request->attributes->get('_route');

			if ( 'syncengine_install_repair' === $currentRoute && str_contains( $exception->getMessage(), '.user' ) ) {
				$this->tokenStorage?->setToken(null);
				$this->rememberMeService?->clearRememberMeCookie();
			}

			$event->allowCustomResponseCode();
			$event->setResponse(
				new RedirectResponse( $this->urlGenerator->generate( 'syncengine_install_repair' ) )
			);
		}
	}

	public function logException( ExceptionEvent $event ): void {
		// Nothing yet.
	}

	public function notifyException( ExceptionEvent $event ): void {
		// Nothing yet.
	}
}
