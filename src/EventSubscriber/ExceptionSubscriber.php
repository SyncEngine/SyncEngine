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
use SyncEngine\Framework\ModuleRegistryManager;
use SyncEngine\Service\System;

class ExceptionSubscriber implements EventSubscriberInterface
{
	private const MODULE_RECOVERY_PARAM = '_module_recovery';
	private const MODULE_RECOVERY_MAX   = 10;

	public function __construct(
		private readonly KernelInterface $kernel,
		private readonly RequestStack $requestStack,
		private readonly UrlGeneratorInterface $urlGenerator,
		private readonly TokenStorageInterface $tokenStorage,
		private readonly ModuleRegistryManager $moduleRegistryManager,
		private readonly System $system,
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
		if ( $this->kernel->isDebug() ) {
			return;
		}
		$request   = $this->requestStack->getCurrentRequest();

		if ( ! $request ) {
			return;
		}

		$exception = $event->getThrowable();

		if ( $exception instanceof \ErrorException ) {
			$recoveryAttempt = (int) $request->query->get( self::MODULE_RECOVERY_PARAM, 0 );

			if ( $recoveryAttempt < self::MODULE_RECOVERY_MAX ) {
				$packages = $this->moduleRegistryManager->extractModulePackagesFromThrowable( $exception );

				if ( $packages ) {
					if ( 0 === $recoveryAttempt ) {
						$this->system->runCommand( 'cache:clear', silent: false );
						$event->allowCustomResponseCode();
						$event->setResponse(
							new RedirectResponse(
								$this->urlGenerator->generate(
									'syncengine_modules',
									[ self::MODULE_RECOVERY_PARAM => 1 ]
								)
							)
						);

						return;
					}

					$this->moduleRegistryManager->appendDisabledModules(
						$packages,
						'Auto-disabled after runtime module exception: ' . $exception->getMessage()
					);

					$this->system->runCommand( 'cache:clear', silent: false );

					if ( $request->hasSession() ) {
						$request->getSession()->getFlashBag()->add(
							'warning',
							'Modules were auto-disabled after runtime errors: ' . implode( ', ', $packages )
						);
					}

					$event->allowCustomResponseCode();
					$event->setResponse(
						new RedirectResponse(
							$this->urlGenerator->generate(
								'syncengine_modules',
								[ self::MODULE_RECOVERY_PARAM => $recoveryAttempt + 1 ]
							)
						)
					);

					return;
				}
			}
		}

		if ( $exception instanceof TableNotFoundException ) {

			$currentRoute = $request->attributes->get( '_route' );

			if ( 'syncengine_install_repair' === $currentRoute && str_contains( $exception->getMessage(), '.user' ) ) {
				$this->tokenStorage?->setToken( null );
				$this->rememberMeService?->clearRememberMeCookie();
			}

			$error = $request->query->get( 'error' ) ?: $exception->getMessage();

			$event->allowCustomResponseCode();
			$event->setResponse(
				new RedirectResponse( $this->urlGenerator->generate( 'syncengine_install_repair', [ 'error' => $error ] ) )
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
