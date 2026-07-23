<?php

namespace SyncEngine\Tests\Webservice;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Tests\Mock\Webservice\MockSoapMultistep;
use SyncEngine\Tests\TestCase\BaseTestCase;

class SoapMultistepTest extends BaseTestCase
{
	public function setUp(): void
	{
		parent::setUp();
		MockSoapMultistep::resetMockState();
	}

	/**
	 * @return MockSoapMultistep
	 */
	protected function getMockSoapMultistep(): MockSoapMultistep
	{
		return WebserviceModel::get( 'MockSoapMultistep' );
	}

	// ── authorizeStep ─────────────────────────────────────────────────────────

	public function testAuthorizeStepSuccess(): void
	{
		$mock = $this->getMockSoapMultistep();
		$mock::primeMockResponses( [
			[
				'body' => [ 'token' => 'abc123', 'expires_in' => 3600 ],
				'status' => 200,
				'soap_response' => '<soap:Envelope><soap:Body><AuthResponse><token>abc123</token></AuthResponse></soap:Body></soap:Envelope>',
			],
		] );

		$connection = ConnectionModel::create();

		$authConfig = [
			'request' => [
				'wsdl_mode'     => true,
				'wsdl_url'      => 'https://auth.example.com/service?wsdl',
				'soap_initiate' => 'Authenticate',
				'call_data'     => [ 'username' => 'test', 'password' => 'secret' ],
			],
		];

		$result = $mock->authorizeStep( $authConfig, $connection );

		$this->assertTrue( $result->isSuccess() );
		$this->assertEquals( [ 'token' => 'abc123', 'expires_in' => 3600 ], $result->getData() );

		$requests = MockSoapMultistep::getMockAuthRequests();
		$this->assertCount( 1, $requests );
		$this->assertEquals( 'Authenticate', $requests[0]['method'] );
		$this->assertEquals( [ 'Authenticate' => [ 'username' => 'test', 'password' => 'secret' ] ], $requests[0]['args'] );
	}

	public function testAuthorizeStepNonWsdLMode(): void
	{
		$mock = $this->getMockSoapMultistep();
		$mock::primeMockResponses( [
			[
				'body' => [ 'token' => 'xyz789' ],
				'status' => 200,
			],
		] );

		$connection = ConnectionModel::create();

		$authConfig = [
			'request' => [
				'wsdl_mode'     => false,
				'host'          => 'https://auth.example.com',
				'endpoint'      => '/token',
				'soap_initiate' => 'GetToken',
				'call_data'     => [ 'grant_type' => 'client_credentials' ],
			],
		];

		$result = $mock->authorizeStep( $authConfig, $connection );

		$this->assertTrue( $result->isSuccess() );

		$requests = MockSoapMultistep::getMockAuthRequests();
		$this->assertFalse( $requests[0]['wsdl_mode'] );
		$this->assertEquals( 'https://auth.example.com/token', $requests[0]['location'] );
	}

	// ── SOAP Options in Auth ──────────────────────────────────────────────────

	public function testAuthorizeStepWithSoapVersion(): void
	{
		$mock = $this->getMockSoapMultistep();
		$mock::primeMockResponses( [
			[ 'body' => [ 'token' => 't' ], 'status' => 200 ],
		] );

		$connection = ConnectionModel::create();

		$authConfig = [
			'request' => [
				'wsdl_mode'     => true,
				'wsdl_url'      => 'https://auth.example.com/service?wsdl',
				'soap_initiate' => 'Authenticate',
				'soap_version'  => '1.2',
			],
		];

		$mock->authorizeStep( $authConfig, $connection );

		$requests = MockSoapMultistep::getMockAuthRequests();
		$this->assertEquals( \SOAP_1_2, $requests[0]['soap_version'] );
	}

	public function testAuthorizeStepWithCompression(): void
	{
		$mock = $this->getMockSoapMultistep();
		$mock::primeMockResponses( [
			[ 'body' => [ 'token' => 't' ], 'status' => 200 ],
		] );

		$connection = ConnectionModel::create();

		$authConfig = [
			'request' => [
				'wsdl_mode'     => true,
				'wsdl_url'      => 'https://auth.example.com/service?wsdl',
				'soap_initiate' => 'Authenticate',
				'compression'   => 'gzip',
			],
		];

		$mock->authorizeStep( $authConfig, $connection );

		$requests = MockSoapMultistep::getMockAuthRequests();
		$this->assertEquals( \SOAP_COMPRESSION_GZIP, $requests[0]['compression'] );
	}

	public function testAuthorizeStepWithLoginPassword(): void
	{
		$mock = $this->getMockSoapMultistep();
		$mock::primeMockResponses( [
			[ 'body' => [ 'token' => 't' ], 'status' => 200 ],
		] );

		$connection = ConnectionModel::create();

		$authConfig = [
			'request' => [
				'wsdl_mode'     => true,
				'wsdl_url'      => 'https://auth.example.com/service?wsdl',
				'soap_initiate' => 'Authenticate',
				'username'      => 'apiuser',
				'password'      => 'apipass',
			],
		];

		$mock->authorizeStep( $authConfig, $connection );

		$requests = MockSoapMultistep::getMockAuthRequests();
		$this->assertEquals( 'apiuser', $requests[0]['login'] );
		$this->assertEquals( 'apipass', $requests[0]['password'] );
	}

	// ── SOAPAction in Auth ────────────────────────────────────────────────────

	public function testAuthorizeStepWithSoapAction(): void
	{
		$mock = $this->getMockSoapMultistep();
		$mock::primeMockResponses( [
			[ 'body' => [ 'token' => 't' ], 'status' => 200 ],
		] );

		$connection = ConnectionModel::create();

		$authConfig = [
			'request' => [
				'wsdl_mode'     => true,
				'wsdl_url'      => 'https://auth.example.com/service?wsdl',
				'soap_initiate' => 'Authenticate',
				'soap_action'   => 'http://auth.example.com/Authenticate',
			],
		];

		$mock->authorizeStep( $authConfig, $connection );

		$requests = MockSoapMultistep::getMockAuthRequests();
		$this->assertEquals( 'http://auth.example.com/Authenticate', $requests[0]['soap_action'] );
	}

	// ── Auth Headers ──────────────────────────────────────────────────────────

	public function testAuthorizeStepCapturesHeaders(): void
	{
		$mock = $this->getMockSoapMultistep();
		$mock::primeMockResponses( [
			[ 'body' => [ 'token' => 't' ], 'status' => 200 ],
		] );

		$connection = ConnectionModel::create();

		$authConfig = [
			'request' => [
				'wsdl_mode'     => true,
				'wsdl_url'      => 'https://auth.example.com/service?wsdl',
				'soap_initiate' => 'Authenticate',
				'headers'       => [
					[ 'url' => 'http://example.com/ns', 'key' => 'AuthNonce', 'value' => 'nonce123' ],
				],
			],
		];

		$mock->authorizeStep( $authConfig, $connection );

		$requests = MockSoapMultistep::getMockAuthRequests();
		$this->assertCount( 1, $requests[0]['headers'] );
		$this->assertEquals( 'AuthNonce', $requests[0]['headers'][0]['key'] );
		$this->assertEquals( 'http://example.com/ns', $requests[0]['headers'][0]['namespace'] );
	}

	// ── parseAuthStepResponseType ─────────────────────────────────────────────

	public function testParseAuthStepResponseTypeScalar(): void
	{
		$mock = $this->getMockSoapMultistep();

		$result = $mock->parseAuthStepResponseType( 'simple string', [] );
		$this->assertEquals( 'simple string', $result );

		$result = $mock->parseAuthStepResponseType( 123, [] );
		$this->assertEquals( 123, $result );

		$result = $mock->parseAuthStepResponseType( true, [] );
		$this->assertTrue( $result );
	}

	public function testParseAuthStepResponseTypeEmptyArray(): void
	{
		$mock = $this->getMockSoapMultistep();

		$response = [];
		$tagConfig = [ 'tag' => 'token' ];

		$result = $mock->parseAuthStepResponseType( $response, $tagConfig );
		$this->assertEquals( [], $result );
	}

	public function testParseAuthStepResponseTypeEmptyString(): void
	{
		$mock = $this->getMockSoapMultistep();

		$result = $mock->parseAuthStepResponseType( '', [] );
		$this->assertEquals( '', $result );
	}

	public function testParseAuthStepResponseTypeNull(): void
	{
		$mock = $this->getMockSoapMultistep();

		$result = $mock->parseAuthStepResponseType( null, [] );
		$this->assertNull( $result );
	}

	// ── XML Parsing ───────────────────────────────────────────────────────────

	public function testParseAuthStepResponseTypeXmlWithSoapBody(): void
	{
		$mock = $this->getMockSoapMultistep();

		$xml = '<?xml version="1.0"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><AuthenticateResponse><token>xml-token-123</token></AuthenticateResponse></soap:Body></soap:Envelope>';

		$parsed = $mock->parseAuthStepResponseType( $xml );

		$result = ( new TagParser( $parsed ) )->parseTag( 'AuthenticateResponse.token' );

		$this->assertEquals( 'xml-token-123', $result );
	}

	public function testParseAuthStepResponseTypeXmlWithNamespacedElement(): void
	{
		$mock = $this->getMockSoapMultistep();

		$xml = '<?xml version="1.0"?><root xmlns:ns="http://example.com/ns"><ns:Token>ns-token-456</ns:Token></root>';

		$parsed = $mock->parseAuthStepResponseType( $xml );

		$result = ( new TagParser( $parsed ) )->parseTag( 'ns:Token' );

		$this->assertEquals( 'ns-token-456', $result );
	}

	public function testParseAuthStepResponseTypeXmlNestedStructure(): void
	{
		$mock = $this->getMockSoapMultistep();

		$xml = '<?xml version="1.0"?><root><response><data><id>789</id><name>test</name></data></response></root>';

		$parsed = $mock->parseAuthStepResponseType( $xml );

		$result = ( new TagParser( $parsed ) )->parseTag( 'response.data.id' );

		$this->assertEquals( '789', $result );
	}

	public function testParseAuthStepResponseTypeInvalidXml(): void
	{
		$mock = $this->getMockSoapMultistep();

		$xml = '<invalid xml>';

		$parsed = $mock->parseAuthStepResponseType( $xml );

		$this->assertEmpty( $parsed );
	}

	// ── Connection Model Handling ─────────────────────────────────────────────

	public function testAuthorizeStepWithConnectionId(): void
	{
		$mock = $this->getMockSoapMultistep();
		$mock::primeMockResponses( [
			[ 'body' => [ 'token' => 't' ], 'status' => 200 ],
		] );

		$connection = ConnectionModel::create();
		$connection->setData( [ 'tags' => [ 'token' => '' ] ], 'auth' );

		$authConfig = [
			'request' => [
				'wsdl_mode'     => true,
				'wsdl_url'      => 'https://auth.example.com/service?wsdl',
				'soap_initiate' => 'Authenticate',
			],
			'_ref'        => 'auth_step_1',
		];

		$result = $mock->authorizeStep( $authConfig, $connection->getId() );

		$this->assertTrue( $result->isSuccess() );
	}

	// ── Reset State ───────────────────────────────────────────────────────────

	public function testResetMockState(): void
	{
		$mock = $this->getMockSoapMultistep();
		$mock::primeMockResponses( [
			[ 'body' => [ 'token' => 't' ], 'status' => 200 ],
		] );

		$connection = ConnectionModel::create();

		$authConfig = [
			'request' => [
				'wsdl_mode'     => true,
				'wsdl_url'      => 'https://auth.example.com/service?wsdl',
				'soap_initiate' => 'Authenticate',
			],
		];

		$mock->authorizeStep( $authConfig, $connection );
		$this->assertCount( 1, MockSoapMultistep::getMockAuthRequests() );

		MockSoapMultistep::resetMockState();
		$this->assertCount( 0, MockSoapMultistep::getMockAuthRequests() );
	}

	// ── Multiple Auth Steps ───────────────────────────────────────────────────

	public function testMultipleSequentialAuthSteps(): void
	{
		$mock = $this->getMockSoapMultistep();
		$mock::primeMockResponses( [
			[ 'body' => [ 'step1' => true ], 'status' => 200 ],
			[ 'body' => [ 'step2' => true ], 'status' => 200 ],
		] );

		$connection = ConnectionModel::create();

		$authConfig1 = [
			'request' => [
				'wsdl_mode'     => true,
				'wsdl_url'      => 'https://auth.example.com/service?wsdl',
				'soap_initiate' => 'Step1',
			],
		];

		$authConfig2 = [
			'request' => [
				'wsdl_mode'     => true,
				'wsdl_url'      => 'https://auth.example.com/service?wsdl',
				'soap_initiate' => 'Step2',
			],
		];

		$mock->authorizeStep( $authConfig1, $connection );
		$mock->authorizeStep( $authConfig2, $connection );

		$requests = MockSoapMultistep::getMockAuthRequests();
		$this->assertCount( 2, $requests );
		$this->assertEquals( 'Step1', $requests[0]['method'] );
		$this->assertEquals( 'Step2', $requests[1]['method'] );
	}

	// ── getAuthFields ─────────────────────────────────────────────────────────

	public function testGetAuthFields(): void
	{
		$mock = $this->getMockSoapMultistep();
		$fields = $mock->getAuthFields();

		$this->assertNotNull( $fields->get( 'host' ) );
		// Multistep auth adds 'variables', 'authorization', 'authorize' fields
		$this->assertNotNull( $fields->get( 'variables' ) );
		$this->assertNotNull( $fields->get( 'authorization' ) );
		$this->assertNotNull( $fields->get( 'authorize' ) );
	}

	// ── getAuthStepRequestFields ──────────────────────────────────────────────

	public function testGetAuthStepRequestFields(): void
	{
		$mock = $this->getMockSoapMultistep();
		$fields = $mock->getAuthStepRequestFields();

		$this->assertNotNull( $fields->get( 'url' ) );
		// Should also include fields from getRetrieveFields
		$this->assertNotNull( $fields->get( 'wsdl_mode' ) );
		$this->assertNotNull( $fields->get( 'soap_initiate' ) );
	}

	// ── Webservice Properties ─────────────────────────────────────────────────

	public function testWebserviceProperties(): void
	{
		$mock = $this->getMockSoapMultistep();

		$this->assertEquals( 'soap', $mock->getType() );
		$this->assertEquals( 'webservice-soap-multistep', $mock->getIcon() );
		$this->assertEquals( 'SOAP Multistep Mock (tests)', $mock->getName() );
		$this->assertStringContainsString( 'SOAP', $mock->getDescription() );
	}
}
