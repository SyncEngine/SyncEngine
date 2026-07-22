<?php

namespace SyncEngine\Security\Scope;

/**
 * Defines all available scopes and provides utility methods for
 * validation, expansion, and extraction.
 *
 * This is a general-purpose scope definition, not tied to any
 * specific authorization mechanism (API tokens, admin roles, etc.).
 */
class ScopeRegistry
{
	/**
	 * All valid scopes in the system.
	 */
	public const ALL = [
		// Entity CRUD scopes (populated from ENTITY_SCOPES)
		'automation:read',
		'automation:create',
		'automation:update',
		'automation:delete',
		'automation:run',
		'connection:read',
		'connection:create',
		'connection:update',
		'connection:delete',
		'flow:read',
		'flow:create',
		'flow:update',
		'flow:delete',
		'routine:read',
		'routine:create',
		'routine:update',
		'routine:delete',
		'storage:read',
		'storage:create',
		'storage:update',
		'storage:delete',
		// Wildcard (full access)
		'*',
	];

	/**
	 * Mapping of entity names to their available scopes.
	 */
	public const ENTITY_SCOPES = [
		'automation' => [
			'automation:read',
			'automation:create',
			'automation:update',
			'automation:delete',
			'automation:run',
		],
		'connection' => [
			'connection:read',
			'connection:create',
			'connection:update',
			'connection:delete',
		],
		'flow' => [
			'flow:read',
			'flow:create',
			'flow:update',
			'flow:delete',
		],
		'routine' => [
			'routine:read',
			'routine:create',
			'routine:update',
			'routine:delete',
		],
		'storage' => [
			'storage:read',
			'storage:create',
			'storage:update',
			'storage:delete',
		],
	];

	/**
	 * Wildcard scope that grants full access.
	 */
	public const WILDCARD = '*';

	/**
	 * Check if a scope is valid.
	 */
	public static function isValid( string $scope ): bool
	{
		return in_array( $scope, self::ALL, true );
	}

	/**
	 * Expand a scope string into individual scopes.
	 *
	 * Handles wildcards:
	 * - 'automation:*' expands to all automation:read, automation:write, etc.
	 * - '*' stays as '*' (full access)
	 */
	public static function expand( string $scope ): array
	{
		// Bare wildcard returns itself as a single scope.
		if ( $scope === self::WILDCARD ) {
			return [ self::WILDCARD ];
		}

		// Entity wildcard: 'automation:*' -> all automation scopes.
		if ( str_ends_with( $scope, ':*' ) ) {
			$entity = substr( $scope, 0, -2 );
			if ( isset( self::ENTITY_SCOPES[ $entity ] ) ) {
				return self::ENTITY_SCOPES[ $entity ];
			}
			return [ $scope ];
		}

		// Single scope, return as-is.
		return [ $scope ];
	}

	/**
	 * Expand a list of scopes into individual scopes.
	 *
	 * Wildcards are expanded. Invalid scopes are silently dropped.
	 */
	public static function expandAll( array $scopes ): array
	{
		$expanded = [];

		foreach ( $scopes as $scope ) {
			if ( ! self::isValid( $scope ) ) {
				continue;
			}

			$expanded = array_merge( $expanded, self::expand( $scope ) );
		}

		return array_values( array_unique( $expanded ) );
	}

	/**
	 * Check if a scope is contained in a list of scopes.
	 *
	 * Handles wildcard matching:
	 * - If the list contains '*', any scope is allowed.
	 * - If the list contains 'entity:*', any 'entity:*' scope is allowed.
	 */
	public static function isAllowed( string $scope, array $scopes ): bool
	{
		// Wildcard grants everything.
		if ( in_array( self::WILDCARD, $scopes, true ) ) {
			return true;
		}

		// Direct match.
		if ( in_array( $scope, $scopes, true ) ) {
			return true;
		}

		// Entity wildcard: 'automation:*' allows 'automation:read'.
		$entity = self::getEntityName( $scope );
		if ( $entity && in_array( $entity . ':*', $scopes, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Extract the entity name from a scope string.
	 *
	 * 'automation:read' -> 'automation'
	 * 'automation:*' -> 'automation'
	 */
	public static function getEntityName( string $scope ): ?string
	{
		if ( str_contains( $scope, ':' ) ) {
			return substr( $scope, 0, strpos( $scope, ':' ) );
		}

		return null;
	}

	/**
	 * Get the action from a scope string.
	 *
	 * 'automation:read' -> 'read'
	 * 'automation:*' -> '*'
	 */
	public static function getAction( string $scope ): ?string
	{
		if ( str_contains( $scope, ':' ) ) {
			return substr( $scope, strpos( $scope, ':' ) + 1 );
		}

		return null;
	}

	/**
	 * Get all scopes for a specific entity.
	 */
	public static function getEntityScopes( string $entity ): array
	{
		return self::ENTITY_SCOPES[ $entity ] ?? [];
	}

	/**
	 * Get all entity names that have scopes.
	 */
	public static function getEntities(): array
	{
		return array_keys( self::ENTITY_SCOPES );
	}
}
