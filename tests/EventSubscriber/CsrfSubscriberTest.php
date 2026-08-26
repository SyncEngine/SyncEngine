<?php

namespace SyncEngine\Tests\EventSubscriber;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\SameOriginCsrfTokenManager;
use SyncEngine\EventSubscriber\CsrfSubscriber;

class CsrfSubscriberTest extends WebTestCase
{
	private CsrfSubscriber $subscriber;

	private KernelBrowser $client;

	protected function setUp(): void
	{
		$this->client = static::createClient();

		/**
		 * We use stateless tokens for JSON requests.
		 * Validation should be done by comparing the token in the cookie with the token in the request header.
		 * This is a security measure to prevent CSRF attacks on JSON endpoints.
		 *
		 * @see SameOriginCsrfTokenManager
		 */
		$this->subscriber = static::getContainer()->get( CsrfSubscriber::class );
	}

	public function testNonMainRequestIsIgnored(): void
	{
		$request = Request::create( '/json/test', 'POST' );
		$request->cookies->set( 'csrf-token', 'any-token' );
		$request->headers->set( 'csrf-token', 'any-token' );

		$event = $this->createRequestEvent( $request, false );

		$this->subscriber->onRequest( $event );

		$this->assertNull( $event->getResponse() );
	}

	public function testNonJsonPathIsIgnored(): void
	{
		$request = Request::create( '/admin/test', 'POST' );
		$request->cookies->set( 'csrf-token', 'any-token' );
		$request->headers->set( 'csrf-token', 'some-other-token' );

		$event = $this->createRequestEvent( $request, true );

		$this->subscriber->onRequest( $event );

		$this->assertNull( $event->getResponse() );
	}

	public function testGetJsonPathWithoutCsrfTokenReturns403(): void
	{
		$request = Request::create( '/json/tasks', 'GET' );

		$event = $this->createRequestEvent( $request, true );

		$this->subscriber->onRequest( $event );

		$response = $event->getResponse();
		$this->assertInstanceOf( JsonResponse::class, $response );
		$this->assertEquals( 403, $response->getStatusCode() );
		$data = json_decode( $response->getContent(), true );
		$this->assertEquals( 'CSRF token missing', $data['error'] );
	}

	public function testMissingCookieReturns403(): void
	{
		$request = Request::create( '/json/preferences', 'POST' );
		$request->headers->set( 'csrf-token', 'some-token' );

		$event = $this->createRequestEvent( $request, true );

		$this->subscriber->onRequest( $event );

		$response = $event->getResponse();
		$this->assertInstanceOf( JsonResponse::class, $response );
		$this->assertEquals( 403, $response->getStatusCode() );
		$data = json_decode( $response->getContent(), true );
		$this->assertEquals( 'CSRF token missing', $data['error'] );
	}

	public function testMatchingTokenPasses(): void
	{
		$token = 'test-match-' . bin2hex( random_bytes( 8 ) );

		$request = Request::create( '/json/preferences', 'POST' );
		$request->cookies->set( 'csrf-token', $token );
		$request->headers->set( 'csrf-token', $token );

		$event = $this->createRequestEvent( $request, true );

		$this->subscriber->onRequest( $event );

		$this->assertNull( $event->getResponse() );
	}

	public function testMismatchedTokenReturns403(): void
	{
		$request = Request::create( '/json/preferences', 'POST' );
		$request->cookies->set( 'csrf-token', 'cookie-value' );
		$request->headers->set( 'csrf-token', 'header-value' );

		$event = $this->createRequestEvent( $request, true );

		$this->subscriber->onRequest( $event );

		$response = $event->getResponse();
		$this->assertInstanceOf( JsonResponse::class, $response );
		$this->assertEquals( 403, $response->getStatusCode() );
		$data = json_decode( $response->getContent(), true );
		$this->assertEquals( 'Invalid CSRF token', $data['error'] );
	}

	public function testPutJsonPathRequiresToken(): void
	{
		$request = Request::create( '/json/preferences', 'PUT' );

		$event = $this->createRequestEvent( $request, true );

		$this->subscriber->onRequest( $event );

		$response = $event->getResponse();
		$this->assertInstanceOf( JsonResponse::class, $response );
		$this->assertEquals( 403, $response->getStatusCode() );
	}

	public function testDeleteJsonPathRequiresToken(): void
	{
		$request = Request::create( '/json/preferences', 'DELETE' );

		$event = $this->createRequestEvent( $request, true );

		$this->subscriber->onRequest( $event );

		$response = $event->getResponse();
		$this->assertInstanceOf( JsonResponse::class, $response );
		$this->assertEquals( 403, $response->getStatusCode() );
	}

	private function createRequestEvent( Request $request, bool $isMainRequest ): \Symfony\Component\HttpKernel\Event\RequestEvent
	{
		$requestStack = static::getContainer()->get( 'request_stack' );
		$requestStack->push( $request );

		return new \Symfony\Component\HttpKernel\Event\RequestEvent(
			$this->client->getKernel(),
			$request,
			$isMainRequest ? \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST : \Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST,
		);
	}
}
