<?php

namespace SyncEngine\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use SyncEngine\Entity\ApiToken;
use SyncEngine\Repository\ApiTokenRepository;
use SyncEngine\Repository\UserRepository;
use SyncEngine\Security\Badge\ApiTokenBadge;
use SyncEngine\Security\Scope\ScopeRegistry;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
	public function __construct(
		#[Autowire( '%env(SYNCENGINE_API_TOKEN_HEADER)%' )]
		private readonly ?string $header,
		private readonly UserRepository $userRepository,
		private readonly ApiTokenRepository $apiTokenRepository,
		protected readonly LoggerInterface $syncengineLogger,
	) {}

	public function supports( Request $request ): ?bool
	{
		return str_starts_with( $request->getPathInfo(), '/api/' );
	}

	public function authenticate( Request $request ): Passport
	{
		if ( empty( $this->header ) ) {
			$authorization = $request->headers->get( 'Authorization' );
			// Bearer token.
			$apiToken = substr( $authorization, 7 );
		} else {
			$apiToken = $request->headers->get( $this->header );
		}

		if ( empty( $apiToken ) ) {
			throw new CustomUserMessageAuthenticationException( 'No API Token provided' );
		}

		// Resolve the user and ApiToken entity.
		$user = $this->userRepository->findByApiToken( $apiToken );

		if ( ! $user ) {
			throw new UserNotFoundException();
		}

		// Load and validate scopes.
		$apiTokenEntity = $this->apiTokenRepository->findOneBy( [ 'token' => $apiToken ] );

		if ( ! $this->isTokenValid( $apiTokenEntity, $request ) ) {
			throw new CustomUserMessageAuthenticationException( 'Invalid API Token' );
		}

		$rawScopes = $apiTokenEntity->getConfig()['scopes'] ?? [];

		// No scopes = no access (no backward compatibility).
		if ( empty( $rawScopes ) ) {
			throw new CustomUserMessageAuthenticationException( 'Token has no scopes assigned' );
		}

		// Validate each scope.
		$invalidScopes = array_filter( $rawScopes, fn( $scope ) => ! ScopeRegistry::isValid( $scope ) );
		if ( ! empty( $invalidScopes ) ) {
			throw new CustomUserMessageAuthenticationException( 'Token has invalid scopes: ' . implode( ', ', $invalidScopes ) );
		}

		// Inject scopes as custom token data.
		$passport = new SelfValidatingPassport(
			new UserBadge( $apiToken, fn() => $user ),
			[ new ApiTokenBadge( $apiTokenEntity ) ]
		);

		return $passport;
	}

	public function createToken( Passport $passport, string $firewallName ): TokenInterface
	{
		$apiTokenBadge = $passport->getBadge( ApiTokenBadge::class );

		if ( ! $apiTokenBadge instanceof ApiTokenBadge ) {
			throw new \LogicException( 'ApiTokenBadge not found in passport.' );
		}

		return new ApiTokenSecurityToken(
			$passport->getUser(),
			$apiTokenBadge->getApiToken(),
			$firewallName
		);
	}

	public function isTokenValid( ApiToken|string $apiToken, Request $request ): bool
	{
		if ( ! $apiToken instanceof ApiToken ) {
			$apiToken = $this->apiTokenRepository->findOneBy( [ 'token' => $apiToken ] );
		}

		if ( ! $apiToken instanceof ApiToken ) {
			return false;
		}

		if ( new \DateTime() > $apiToken->getExpires() ) {
			throw new CustomUserMessageAuthenticationException( 'Expired API Token' );
		}

		$config = $apiToken->getConfig();

		if ( empty( $config['restrictions'] ) ) {
			return true;
		}

		try {

			$restrictions = $config['restrictions'];

			$ips = $restrictions['ip'] ?? '';
			if ( $ips && $ips = array_map( 'trim', explode( ',', $ips ) ) ) {
				$ip = $request->getClientIp();
				if ( ! IpUtils::checkIp( $ip, $ips ) ) {
					return false;
				}
			}

			/**
			 * Note: This is not actual security, it's a soft restriction.
			 * The 'origin' header can be spoofed, so this is more of a convenience check than a security measure.
			 */
			$hosts = $restrictions['host'] ?? '';
			if ( $hosts && $hosts = array_map( 'trim', explode( ',', $hosts ) ) ) {
				$host = $request->headers->get( 'origin' ) ?: $request->headers->get( 'HTTP_ORIGIN' );
				if ( ! $host || ! is_string( $host ) ) {
					return in_array( 'localhost', $hosts ) && IpUtils::isPrivateIp( $request->getClientIp() );
				}
				if ( ! $this->validateHost( $host, $hosts ) ) {
					return false;
				}
			}

		} catch ( \Exception $e ) {
			$this->syncengineLogger->error( $e );

			// Do not provide further information to the client, further info can be found in the logs
			throw new AuthenticationException();
		}

		return true;
	}

	public function validateHost( string $host, array $allowedHosts ): bool
	{
		// Normalize domain to ensure it doesn't have a leading protocol.
		$parsedHost = parse_url( $host, PHP_URL_HOST ) ?: $host;

		foreach ( $allowedHosts as $allowedHost ) {
			if ( strcasecmp( $parsedHost, $allowedHost ) === 0 ) {
				return true;
			}

			// Handle host wildcards (e.g., *.example.com).
			if ( str_starts_with( $allowedHost, '*' ) ) {

				$pattern = preg_quote( ltrim( $allowedHost, '*' ), '/' );
				$pattern = '/^([a-z0-9-]+\.)?' . $pattern . '$/i';

				if ( preg_match( $pattern, $parsedHost ) ) {
					return true;
				}
			}
		}

		return false;
	}

	public function onAuthenticationSuccess( Request $request, TokenInterface $token, string $firewallName ): ?Response
	{
		return null;
	}

	public function onAuthenticationFailure( Request $request, AuthenticationException $exception ): ?Response
	{
		$data = [
			'message' => strtr( $exception->getMessageKey(), $exception->getMessageData() ),
		];

		return new JsonResponse( $data, Response::HTTP_UNAUTHORIZED );
	}

	//    public function start(Request $request, AuthenticationException $authException = null): Response
	//    {
	//        /*
	//         * If you would like this class to control what happens when an anonymous user accesses a
	//         * protected page (e.g. redirect to /login), uncomment this method and make this class
	//         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
	//         *
	//         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
	//         */
	//    }
}
