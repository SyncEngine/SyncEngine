<?php

namespace SyncEngine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SyncEngine\Repository\UserRepository;
use SyncEngine\Security\Scope\ScopeRegistry;

#[ORM\Entity( repositoryClass: UserRepository::class )]
#[UniqueEntity( fields: [ 'email' ], message: 'There is already an account with this email' )]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column( length: 180, unique: true )]
	private ?string $email = null;

	/**
	 * @var string The hashed password
	 */
	#[ORM\Column]
	private ?string $password = null;

	#[ORM\Column( length: 255, unique: false )]
	private ?string $name = null;

	#[ORM\Column]
	private array $roles = [];

	#[ORM\Column( nullable: true )]
	protected ?array $settings = [];

	#[ORM\OneToMany( mappedBy: 'user', targetEntity: ApiToken::class, fetch: "EXTRA_LAZY", orphanRemoval: true )]
	private Collection $apiTokens;

	public function __construct()
	{
		$this->apiTokens = new ArrayCollection();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getEmail(): ?string
	{
		return $this->email;
	}

	public function setEmail( string $email ): self
	{
		$this->email = $email;

		return $this;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName( string $name ): self
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * A visual identifier that represents this user.
	 *
	 * @see UserInterface
	 */
	public function getUserIdentifier(): string
	{
		return (string) $this->email;
	}

	/**
	 * @see UserInterface
	 */
	public function getRoles(): array
	{
		$roles = $this->roles;
		// guarantee every user at least has ROLE_USER
		$roles[] = 'ROLE_USER';

		return array_unique( $roles );
	}

	/**
	 * Role-to-scope mapping.
	 *
	 * Each role maps to a set of API scopes that users with that role
	 * can grant to their tokens. Roles are checked in priority order;
	 * the first match wins.
	 */
	private const ROLE_SCOPES = [
		'ROLE_ADMIN' => [ '*' ],
		'ROLE_EDITOR' => [
			'automation:read', 'automation:create', 'automation:update', 'automation:delete', 'automation:run',
			'routine:read', 'routine:create', 'routine:update', 'routine:delete',
			'flow:read', 'flow:create', 'flow:update', 'flow:delete',
			'connection:read', 'connection:create', 'connection:update', 'connection:delete',
			'storage:read', 'storage:create', 'storage:update', 'storage:delete',
		],
		'ROLE_VIEWER' => [
			'automation:read',
			'routine:read',
			'flow:read',
			'connection:read',
			'storage:read',
		],
	];

	/**
	 * Get the scopes this user is allowed to grant to their API tokens.
	 *
	 * Scopes are resolved from roles in priority order. The first matching
	 * role determines the granted scopes. Wildcards are expanded so the
	 * full list of individual scopes is returned.
	 *
	 * @param bool $includeWildcard Whether to include the wildcard scope.
	 *                              Tokens never use wildcards — they must
	 *                              have explicitly defined scopes. The wildcard
	 *                              is only used internally for voter checks.
	 */
	public function getGrantedScopes( bool $includeWildcard = false ): array
	{
		foreach ( self::ROLE_SCOPES as $role => $scopes ) {
			if ( in_array( $role, $this->roles, true ) ) {
				$expanded = ScopeRegistry::expandAll( $scopes );
				if ( ! $includeWildcard ) {
					$wildcard = array_find_key( $expanded, fn( $s ) => $s === '*' );
					if ( $wildcard !== null ) {
						unset( $expanded[ $wildcard ] );
						$expanded = ScopeRegistry::ALL;
						unset( $expanded[ array_find_key( $expanded, fn( $s ) => $s === '*' ) ] );
					}
				}
				return $expanded;
			}
		}

		return [];
	}

	public function setRoles( array $roles ): self
	{
		$this->roles = $roles;

		return $this;
	}

	/**
	 * @see PasswordAuthenticatedUserInterface
	 */
	public function getPassword(): string
	{
		return $this->password;
	}

	public function setPassword( string $password ): self
	{
		$this->password = $password;

		return $this;
	}

	/**
	 * @see UserInterface
	 */
	public function eraseCredentials(): void
	{
		// If you store any temporary, sensitive data on the user, clear it here
		// $this->plainPassword = null;
	}

	public function getSettings(): array
	{
		return (array) $this->settings;
	}

	public function setSettings( array $settings ): self
	{
		$this->settings = $settings;

		return $this;
	}

	public function getSetting( string $key ): mixed
	{
		return $this->settings[ $key ] ?? null;
	}

	public function setSetting( string $key, mixed $value ): self
	{
		$this->settings[ $key ] = $value;

		return $this;
	}

	public function unsetSetting( string $key ): self
	{
		unset( $this->settings[ $key ] );

		return $this;
	}

	/**
	 * @return Collection<int, ApiToken>
	 */
	public function getApiTokens(): Collection
	{
		return $this->apiTokens;
	}

	public function addApiToken( ApiToken $apiToken ): static
	{
		if ( ! $this->apiTokens->contains( $apiToken ) ) {
			$this->apiTokens->add( $apiToken );
			$apiToken->setUser( $this );
		}

		return $this;
	}

	public function removeApiToken( ApiToken $apiToken ): static
	{
		if ( $this->apiTokens->removeElement( $apiToken ) ) {
			// set the owning side to null (unless already changed)
			if ( $apiToken->getUser() === $this ) {
				$apiToken->setUser( null );
			}
		}

		return $this;
	}

	public function getLocale(): ?string
	{
		return $this->getSetting( 'locale' );
	}

	public function setLocale( ?string $locale ): static
	{
		$this->setSetting( 'locale', $locale );

		return $this;
	}
}
