<?php

namespace SyncEngine\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class UserLocaleSubscriber implements EventSubscriberInterface
{
	public function __construct(
		private string $defaultLocale = 'en',
	) {}

	public function onKernelRequest( RequestEvent $event ): void
	{
		$request = $event->getRequest();
		if ( ! $request->hasPreviousSession() ) {
			return;
		}

		// try to see if the locale has been set as a _locale routing parameter
		if ( $locale = $request->attributes->get( '_locale' ) ) {
			$request->getSession()->set( '_locale', $locale );
		} else {
			// if no explicit locale has been set on this request, use one from the session
			$request->setLocale( $request->getSession()->get( '_locale', $this->defaultLocale ) );
		}
	}

	public function onLoginSuccess( LoginSuccessEvent $event ): void
	{
		$user = $event->getUser();

		if ( null !== $user->getLocale() ) {
			$event->getRequest()->getSession()->set( '_locale', $user->getLocale() );
		}
	}

	public static function getSubscribedEvents(): array
	{
		return [
			// must be registered before (i.e. with a higher priority than) the default Locale listener
			KernelEvents::REQUEST => [ [ 'onKernelRequest', 20 ] ],
			LoginSuccessEvent::class => 'onLoginSuccess',
		];
	}
}
