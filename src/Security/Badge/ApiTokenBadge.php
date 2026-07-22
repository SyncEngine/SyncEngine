<?php

namespace SyncEngine\Security\Badge;

use SyncEngine\Entity\ApiToken;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

/**
 * Carries the ApiToken entity through the authentication passport.
 */
class ApiTokenBadge implements BadgeInterface
{
	public function __construct( private readonly ApiToken $apiToken ) {}

	public function getApiToken(): ApiToken
	{
		return $this->apiToken;
	}

	public function isResolved(): bool
	{
		return true;
	}
}
