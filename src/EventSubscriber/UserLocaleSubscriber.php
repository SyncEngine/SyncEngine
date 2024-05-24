<?php
namespace SyncEngine\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class UserLocaleSubscriber implements EventSubscriberInterface
{
	public function __construct(
		private RequestStack $requestStack,
	) {
	}

	public function onLoginSuccess(LoginSuccessEvent $event): void
	{
		$user = $event->getUser();

		if (null !== $user->getLocale()) {
			$this->requestStack->getSession()->set('_locale', $user->getLocale());
		}

	}

	public static function getSubscribedEvents(): array
	{
		return [
			LoginSuccessEvent::class => 'onLoginSuccess',
		];
	}
}
