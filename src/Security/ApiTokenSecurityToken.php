<?php

namespace SyncEngine\Security;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;
use SyncEngine\Entity\ApiToken;
use SyncEngine\Security\Scope\ScopeRegistry;

/**
 * Custom security token for API token authentication.
 *
 * Carries the authenticated User, the ApiToken entity, and the resolved
 * scopes through the security stack. Used exclusively by the API firewall
 * to provide fine-grained scope-based authorization.
 */
class ApiTokenSecurityToken extends AbstractToken
{
	public function __construct(
		private readonly UserInterface $user,
		private readonly ApiToken $apiToken,
		private readonly string $firewallName,
	) {
		parent::__construct( $user->getRoles() );
		$this->setUser( $user );
	}

	/**
	 * Get the authenticated user.
	 */
	public function getUser(): UserInterface
	{
		return $this->user;
	}

	/**
	 * Get the ApiToken entity that was used for authentication.
	 */
	public function getApiToken(): ApiToken
	{
		return $this->apiToken;
	}

	/**
	 * Get the resolved scopes for this token.
	 *
	 * Wildcards are expanded via ScopeRegistry.
	 */
	public function getScopes(): array
	{
		$rawScopes = $this->apiToken->getConfig()['scopes'] ?? [];

		if ( empty( $rawScopes ) ) {
			return [];
		}

		return ScopeRegistry::expandAll( $rawScopes );
	}

	/**
	 * Get the user identifier (email).
	 */
	public function getUserIdentifier(): string
	{
		return $this->user->getUserIdentifier();
	}

	/**
	 * Check if this token is equal to another token.
	 */
	public function isEqualTo( \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $other ): bool
	{
		if ( ! $other instanceof self ) {
			return false;
		}

		return $this->user->getUserIdentifier() === $other->user->getUserIdentifier()
			&& $this->apiToken->getToken() === $other->apiToken->getToken();
	}

	/**
	 * Erase credentials (no sensitive data to erase).
	 */
	public function eraseCredentials(): void
	{
		// Nothing to erase beyond what AbstractToken does.
	}
}
