<?php

namespace SyncEngine\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use SyncEngine\Entity\User;
use SyncEngine\Security\ApiTokenSecurityToken;
use SyncEngine\Security\Scope\ScopeRegistry;

/**
 * Voter for API token scope authorization.
 *
 * Checks syncengine:api:{entity}:{action} attributes (e.g., syncengine:api:automation:read,
 * syncengine:api:routine:create) against the token's granted scopes. Wildcard '*' grants full access but requires
 * ROLE_ADMIN (hardcoded check).
 *
 * The $subject parameter carries the entity/model instance. This
 * enables future ownership checks (e.g., $subject->getOwner() === $user)
 * without changing the voter interface.
 */
class ApiTokenScopeVoter extends Voter
{
	const PREFIX = 'syncengine:api:';

	public static function getScope( string $scope ): string
	{
		return self::PREFIX . $scope;
	}

	protected function supports( string $attribute, mixed $subject ): bool
	{
		return str_starts_with( $attribute, self::PREFIX );
	}

	protected function voteOnAttribute( string $attribute, mixed $subject, TokenInterface $token ): bool
	{
		// Must be an API token context.
		if ( ! $token instanceof ApiTokenSecurityToken ) {
			return false;
		}

		// Extract required scope from attribute (strip 'syncengine:api:' prefix).
		$requiredScope = substr( $attribute, strlen( self::PREFIX ) );

		// Get token scopes.
		$scopes = $token->getScopes();

		// No scopes on token = no access.
		if ( empty( $scopes ) ) {
			return false;
		}

		// Wildcard '*' grants full access, but ONLY if user has ROLE_ADMIN.
		// This is a hardcoded security check, not just a scope match.
		if ( in_array( ScopeRegistry::WILDCARD, $scopes, true ) ) {
			$user = $token->getUser();
			if ( $user instanceof User && in_array( 'ROLE_ADMIN', $user->getRoles(), true ) ) {
				return true;
			}
			// Wildcard but no ROLE_ADMIN = deny.
			return false;
		}

		// Check if the required scope is allowed by the token.
		if ( ScopeRegistry::isAllowed( $requiredScope, $scopes ) ) {
			return true;
		}

		// Future: entity-level ownership check.
		// if ($subject instanceof EngineEntity) {
		//     $user = $token->getUser();
		//     if ($subject->getOwner() === $user) {
		//         return true;
		//     }
		// }

		return false;
	}
}
