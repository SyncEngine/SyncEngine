<?php

namespace SyncEngine\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
use SyncEngine\Repository\ApiTokenRepository;
use SyncEngine\Repository\UserRepository;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
	public function __construct(
		#[Autowire( '%env(SYNCENGINE_API_TOKEN_HEADER)%' )]
		private readonly ?string $header,
		private readonly UserRepository $userRepository,
		private readonly ApiTokenRepository $apiTokenRepository
	) {}

	public function supports( Request $request ): ?bool
	{
		return str_starts_with( $request->getPathInfo(), '/api/' );
	}

	public function authenticate( Request $request ): Passport
	{
		if ( empty( $this->header ) ) {
			$authorization = $request->headers->get( 'Authorization' );
			$apiToken = substr( $authorization, 7 );
		} else {
			$apiToken = $request->headers->get( $this->header );
		}

		if ( null === $apiToken ) {
			throw new CustomUserMessageAuthenticationException( 'No API Token provided' );
		}

		return new SelfValidatingPassport( new UserBadge( $apiToken, function ( $apiToken ) use ( $request ) {
				$user = $this->userRepository->findByApiToken( $apiToken );

				if ( ! $user ) {
					throw new UserNotFoundException();
				}

				if ( ! $this->isTokenValid( $apiToken, $request ) ) {
					throw new AuthenticationException( 'Invalid API Token' );
				}

				return $user;
			} ) );
	}

	public function isTokenValid( $apiToken, Request $request ): bool
	{
		$token = $this->apiTokenRepository->findOneBy( [ 'token' => $apiToken ] );

		if ( new \DateTime() > $token->getExpires() ) {
			throw new AuthenticationException( 'Expired API Token' );
		}

		$config = $token->getConfig();

		if ( empty( $config['restrictions'] ) ) {
			return true;
		}

		$restrictions = $config['restrictions'];

		$ips = $restrictions['ip'] ?? '';
		if ( $ips && $ips = explode( ',', $ips ) ) {
			$ip  = $request->getClientIp();
			if ( ! in_array( $ip, $ips, true ) ) {
				return false;
			}
		}

		$hosts = $restrictions['hosts'] ?? '';
		if ( $hosts && $hosts = explode( ',', $hosts ) ) {
			$host = $request->headers->get('origin') ?: $request->headers->get('HTTP_ORIGIN');
			if ( ! $host || ! is_string( $host ) ) {
				return false;
			}
			$host = parse_url( $host )['host'] ?? '';
			if ( ! in_array( $host, $hosts, true ) ) {
				return false;
			}
		}

		return true;
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
