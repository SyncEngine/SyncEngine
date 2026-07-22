<?php

namespace SyncEngine\Security\Badge;

use SyncEngine\Entity\ApiToken;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

/**
 * Carries the ApiToken entity through the authentication passport.
 */
class ApiTokenBadge implements BadgeInterface
{
	public function __construct(
		private readonly ApiToken $apiToken,
		private bool $resolved = false,
	) {}

	public function getApiToken(): ApiToken
	{
		return $this->apiToken;
	}

	public function setResolved( bool $resolved ): void
	{
		$this->resolved = $resolved;
	}

	public function isResolved(): bool
	{
		return $this->resolved;
	}
}
