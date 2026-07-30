<?php

namespace SyncEngine\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[AsEventListener]
class TrustedIpListener
{
	public function __construct(
		#[Autowire( '%syncengine.security.trusted_ips%' )]
		private string $trustedIps,
	) {}

	public function __invoke( RequestEvent $event ): void
	{
		if ( empty( $this->trustedIps ) ) {
			// No IP restrictions.
			return;
		}

		$request = $event->getRequest();

		$ips = array_map( 'trim', explode( ',', $this->trustedIps ) );

		$ips[] = '127.0.0.1';

		if ( ! IpUtils::checkIp( $request->getClientIp(), $ips ) ) {
			throw new AccessDeniedHttpException();
		}
	}
}
