<?php

namespace SyncEngine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SyncEngine\Repository\UserRepository;

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

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $locale = null;

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
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }
}
