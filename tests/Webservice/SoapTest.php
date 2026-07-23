<?php

namespace SyncEngine\Tests\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Tests\Mock\Webservice\MockSoap;
use SyncEngine\Tests\TestCase\BaseTestCase;

class SoapTest extends BaseTestCase
{
	public function setUp(): void
	{
		parent::setUp();
		MockSoap::resetMockState();
	}

	/**
	 * @return MockSoap
	 */
	protected function getMockSoap(): MockSoap
	{
		return WebserviceModel::get( 'MockSoap' );
	}

	// ── WSDL Mode ─────────────────────────────────────────────────────────────

	public function testRetrieveWsdLMode(): void
	{
		$mock = $this->getMockSoap();

		$mock::primeMockResponses( [
			[
				'body' => [ 'result' => 'success', 'data' => [ 'id' => 123 ] ],
				'status' => 200,
				'soap_response' => '<soap:Envelope><soap:Body><GetResult><return>success</return></GetResult></soap:Body></soap:Envelope>',
			],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'GetResult',
			'call_data'     => [ 'id' => 123 ],
		];

		$result = $mock->retrieve( $config );

		$this->assertTrue( $result->isSuccess() );
		$this->assertEquals( [ 'result' => 'success', 'data' => [ 'id' => 123 ] ], $result->getData() );

		$requests = MockSoap::getMockRequests();
		$this->assertCount( 1, $requests );
		$this->assertEquals( 'GetResult', $requests[0]['method'] );
		$this->assertEquals( [ 'GetResult' => [ 'id' => 123 ] ], $requests[0]['args'] );
		$this->assertTrue( $requests[0]['wsdl_mode'] );
		$this->assertEquals( 'https://soap.example.com/service?wsdl', $requests[0]['wsdl_url'] );
		$this->assertNull( $requests[0]['location'] );
		$this->assertEquals( 1, $requests[0]['trace'] );
		$this->assertTrue( $requests[0]['exceptions'] );
		$this->assertEquals( \WSDL_CACHE_NONE, MockSoap::getLastSoapClientState()['options']['cache_wsdl'] );
	}

	public function testSendWsdLMode(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[
				'body' => [ 'created' => true, 'id' => 456 ],
				'status' => 200,
			],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'CreateItem',
			'call_data'     => [ 'name' => 'test' ],
			'request'       => [
				'body' => true,
			],
		];

		$result = $mock->send( $config, [ 'name' => 'dynamic item', 'value' => 42 ] );

		$this->assertTrue( $result->isSuccess() );

		$requests = MockSoap::getMockRequests();
		$this->assertEquals( 'CreateItem', $requests[0]['method'] );
		$this->assertEquals( [ 'CreateItem' => [ 'name' => 'dynamic item', 'value' => 42 ] ], $requests[0]['args'] );
	}

	public function testSendNonWsdLMode(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[
				'body' => [ 'created' => true ],
				'status' => 200,
			],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/submit',
			'uri'           => 'https://soap.example.com/service',
			'wsdl_mode'     => false,
			'soap_initiate' => 'SubmitData',
			'call_data'     => [ 'payload' => 'test' ],
			'request'       => [
				'body' => true,
			],
		];

		$result = $mock->send( $config, [ 'field' => 'value' ] );

		$this->assertTrue( $result->isSuccess() );

		$requests = MockSoap::getMockRequests();
		$this->assertFalse( $requests[0]['wsdl_mode'] );
		$this->assertEquals( 'https://soap.example.com/submit', $requests[0]['location'] );
		$this->assertEquals( 'https://soap.example.com/service', $requests[0]['uri'] );
		$this->assertEquals( [ 'SubmitData' => [ 'field' => 'value' ] ], $requests[0]['args'] );
	}

	// ── Non-WSDL Mode ─────────────────────────────────────────────────────────

	public function testRetrieveNonWsdLMode(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[
				'body' => [ 'result' => 'ok' ],
				'status' => 200,
			],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/soap',
			'uri'           => 'https://soap.example.com/service',
			'wsdl_mode'     => false,
			'soap_initiate' => 'GetStatus',
			'call_data'     => [ 'status' => 'active' ],
		];

		$result = $mock->retrieve( $config );

		$this->assertTrue( $result->isSuccess() );

		$requests = MockSoap::getMockRequests();
		$this->assertFalse( $requests[0]['wsdl_mode'] );
		$this->assertEquals( 'https://soap.example.com/soap', $requests[0]['location'] );
		$this->assertNull( $requests[0]['wsdl_url'] );
		$this->assertEquals( 'https://soap.example.com/service', $requests[0]['uri'] );
	}

	public function testNonWsdLConfigCreatesNativeSoapClient(): void
	{
		$mock = $this->getMockSoap();

		$client = $mock->createNativeSoapClient( [
			'host'      => 'https://soap.example.com',
			'endpoint'  => '/soap',
			'uri'       => 'https://soap.example.com/service',
			'wsdl_mode' => false,
		] );

		$this->assertInstanceOf( \SoapClient::class, $client );
	}

	// ── SOAP Options ──────────────────────────────────────────────────────────

	public function testSoapVersionOption(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
			'soap_version'  => '1.2',
		];

		$mock->retrieve( $config );

		$requests = MockSoap::getMockRequests();
		$this->assertEquals( \SOAP_1_2, $requests[0]['soap_version'] );
	}

	public function testSoapVersion11Option(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
			'soap_version'  => '1.1',
		];

		$mock->retrieve( $config );

		$requests = MockSoap::getMockRequests();
		$this->assertEquals( \SOAP_1_1, $requests[0]['soap_version'] );
	}

	public function testCompressionGzipOption(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
			'compression'   => 'gzip',
		];

		$mock->retrieve( $config );

		$requests = MockSoap::getMockRequests();
		$this->assertEquals(
			SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 9,
			$requests[0]['compression']
		);
	}

	public function testConnectionTimeoutOption(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'               => 'https://soap.example.com',
			'endpoint'           => '/api',
			'wsdl_mode'          => true,
			'wsdl_url'           => 'https://soap.example.com/service?wsdl',
			'soap_initiate'      => 'Get',
			'connection_timeout' => 30,
		];

		$mock->retrieve( $config );

		$state = MockSoap::getLastSoapClientState();
		$this->assertEquals( 30, $state['options']['connection_timeout'] );
	}

	// ── SOAP Headers ──────────────────────────────────────────────────────────

	public function testSoapHeadersAreCaptured(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
			'headers'       => [
				[ 'url' => 'http://example.com/ns', 'key' => 'AuthToken', 'value' => 'abc123' ],
				[ 'url' => 'http://example.com/ns', 'key' => 'SessionId', 'value' => 'xyz789' ],
			],
		];

		$mock->retrieve( $config );

		$requests = MockSoap::getMockRequests();
		$this->assertCount( 2, $requests[0]['headers'] );

		$headers = $requests[0]['headers'];
		$this->assertEquals( 'AuthToken', $headers[0]['key'] );
		$this->assertEquals( 'http://example.com/ns', $headers[0]['namespace'] );
		$this->assertEquals( 'SessionId', $headers[1]['key'] );
		$this->assertEquals( 'http://example.com/ns', $headers[1]['namespace'] );
	}

	public function testEmptyHeadersReturnEmptyArray(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
			'headers'       => null,
		];

		$mock->retrieve( $config );

		$requests = MockSoap::getMockRequests();
		$this->assertCount( 0, $requests[0]['headers'] );
	}

	public function testSoapActionHeaderIsCaptured(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
			'soap_action'   => 'http://example.com/Get',
		];

		$mock->retrieve( $config );

		$requests = MockSoap::getMockRequests();
		$this->assertEquals( 'http://example.com/Get', $requests[0]['soap_action'] );
		$this->assertCount( 0, $requests[0]['headers'] );
	}

	public function testSoapActionIsPassedToNativeSoapTransport(): void
	{
		$mock = $this->getMockSoap();
		$client = new class( null, [
			'location' => 'https://soap.example.com/api',
			'uri'      => 'https://soap.example.com/service',
			'trace'    => true,
		] ) extends \SoapClient {
			public ?string $action = null;

			public function __doRequest(
				string $request,
				string $location,
				string $action,
				int $version,
				bool $oneWay = false
			): ?string {
				$this->action = $action;

				return '<?xml version="1.0"?>'
					. '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
					. '<SOAP-ENV:Body><ns1:GetResponse xmlns:ns1="https://soap.example.com/service">'
					. '<return>ok</return></ns1:GetResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
			}
		};

		$result = $mock->requestWithNativeSoapClient(
			$client,
			[
				'soap_initiate' => 'Get',
				'soap_action'   => 'https://soap.example.com/actions/Get',
			],
			[ 'Get' => [] ]
		);

		$this->assertEquals( 'ok', $result->getData() );
		$this->assertTrue( $result->isSuccess() );
		$this->assertEquals( 'https://soap.example.com/actions/Get', $client->action );
	}

	public function testNativeClientConfigurationFailureReturnsResult(): void
	{
		$mock = $this->getMockSoap();
		$result = $mock->executeNativeRequest(
			[
				'host'          => 'https://soap.example.com',
				'endpoint'      => '/soap',
				'wsdl_mode'     => false,
				'soap_initiate' => 'Get',
			],
			[ 'Get' => [] ]
		);

		$this->assertFalse( $result->isSuccess() );
		$this->assertTrue( $result->isException() );
		$this->assertStringContainsString( 'namespace', $result->getResponse()->getMessage() );
	}

	public function testSoapFieldsAreAvailableForRetrieveAndSend(): void
	{
		$mock = $this->getMockSoap();

		foreach ( [ $mock->getRetrieveFields(), $mock->getSendFields() ] as $fields ) {
			$this->assertNotNull( $fields->get( 'wsdl_mode' ) );
			$this->assertNotNull( $fields->get( 'soap_initiate' ) );
			$this->assertNotNull( $fields->get( 'request' ) );
			$this->assertNotNull( $fields->get( 'response' ) );
		}
	}

	// ── getRequestUrl ─────────────────────────────────────────────────────────

	public function testGetRequestUrlWsdLMode(): void
	{
		$mock = $this->getMockSoap();

		$config = [
			'host'      => 'https://soap.example.com',
			'endpoint'  => '/api',
			'wsdl_mode' => true,
			'wsdl_url'  => 'https://soap.example.com/service?wsdl',
		];

		$this->assertEquals( 'https://soap.example.com/service?wsdl', $mock->getRequestUrl( $config ) );
	}

	public function testGetRequestUrlNonWsdLMode(): void
	{
		$mock = $this->getMockSoap();

		$config = [
			'host'      => 'https://soap.example.com',
			'endpoint'  => '/soap',
			'wsdl_mode' => false,
		];

		$this->assertEquals( 'https://soap.example.com/soap', $mock->getRequestUrl( $config ) );
	}

	public function testGetRequestUrlWithoutEndpoint(): void
	{
		$mock = $this->getMockSoap();

		$config = [
			'host'      => 'https://soap.example.com',
			'wsdl_mode' => false,
		];

		$this->assertEquals( 'https://soap.example.com', $mock->getRequestUrl( $config ) );
	}

	public function testGetRequestUrlPrefersExplicitUrl(): void
	{
		$mock = $this->getMockSoap();

		$config = [
			'host'      => 'https://soap.example.com',
			'endpoint'  => '/soap',
			'url'       => 'https://override.example.com/soap',
			'wsdl_mode' => false,
		];

		$this->assertEquals( 'https://override.example.com/soap', $mock->getRequestUrl( $config ) );
	}

	public function testExplicitUrlOverridesNativeWsdLLocation(): void
	{
		$mock = $this->getMockSoap();
		$client = $mock->createNativeSoapClient( [
			'wsdl_mode' => true,
			'wsdl_url'  => dirname( __DIR__ ) . '/Fixtures/soap.wsdl',
			'url'       => 'https://override.example.com/soap',
		] );

		$this->assertEquals( 'https://override.example.com/soap', $client->__setLocation() );
	}

	// ── setSoapHeaders ────────────────────────────────────────────────────────

	public function testSetSoapHeadersReturnsArray(): void
	{
		$mock = $this->getMockSoap();

		$result = $mock->setSoapHeaders( [ 'headers' => null ] );
		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );

		$result = $mock->setSoapHeaders( [ 'headers' => [] ] );
		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );
	}

	public function testSetSoapHeadersCreatesSoapHeaders(): void
	{
		$mock = $this->getMockSoap();

		$config = [
			'headers' => [
				[ 'url' => 'http://example.com/ns', 'key' => 'Token', 'value' => '123' ],
			],
		];

		$result = $mock->setSoapHeaders( $config );
		$this->assertCount( 1, $result );
		$this->assertInstanceOf( \SoapHeader::class, $result[0] );
	}

	// ── Response Format Decoding ──────────────────────────────────────────────

	public function testRetrieveWithResponseFormatDecoding(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[
				'body' => [ 'decoded' => true ],
				'status' => 200,
				'soap_response' => '<?xml version="1.0"?><root><item>value</item></root>',
			],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
			'response'      => [
				'format' => 'xml',
			],
		];

		$result = $mock->retrieve( $config );

		$this->assertTrue( $result->isSuccess() );
		// The decoded result should be different from the raw body
		$this->assertNotEquals( [ 'decoded' => true ], $result->getData() );
	}

	// ── Debug Info ────────────────────────────────────────────────────────────

	public function testRetrieveReturnsSoapDebugInfo(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[
				'body' => [ 'ok' ],
				'status' => 200,
				'soap_response' => '<soap:Body><result>ok</result></soap:Body>',
				'soap_request'  => '<soap:Body><request>data</request></soap:Body>',
			],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
		];

		$result = $mock->retrieve( $config );

		$debugInfo = $result->getDebugInfo();
		$this->assertArrayHasKey( 'SoapResponse', $debugInfo );
		$this->assertArrayHasKey( 'SoapRequest', $debugInfo );
		$this->assertEquals( '<soap:Body><result>ok</result></soap:Body>', $debugInfo['SoapResponse'] );
		$this->assertEquals( '<soap:Body><request>data</request></soap:Body>', $debugInfo['SoapRequest'] );
	}

	// ── Edge Cases ────────────────────────────────────────────────────────────

	public function testRetrieveWithEmptyCallData(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
			'call_data'     => [],
		];

		$result = $mock->retrieve( $config );

		$this->assertTrue( $result->isSuccess() );
		$requests = MockSoap::getMockRequests();
		// With null data and body config, should fall back to call_data
		$this->assertEquals( [ 'Get' => [] ], $requests[0]['args'] );
	}

	public function testRetrieveWithNoWsdlUrlInWsdLMode(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'soap_initiate' => 'Get',
		];

		$result = $mock->retrieve( $config );

		$this->assertTrue( $result->isSuccess() );
		$requests = MockSoap::getMockRequests();
		$this->assertNull( $requests[0]['wsdl_url'] );
	}

	public function testSendWithDynamicData(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'created' => true ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Create',
			'request'       => [
				'body'   => true,
				'format' => 'json',
			],
		];

		$data = [ 'name' => 'test', 'value' => 123 ];
		$result = $mock->send( $config, $data );

		$this->assertTrue( $result->isSuccess() );
		$requests = MockSoap::getMockRequests();
		// With JSON format, the data is encoded to a JSON string
		$this->assertEquals( [ 'Create' => '{"name":"test","value":123}' ], $requests[0]['args'] );
	}

	public function testSendWithoutBodyConfigUsesCallData(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
			'call_data'     => [ 'static' => true ],
		];
		$result = $mock->send( $config, [ 'dynamic' => true ] );

		$this->assertTrue( $result->isSuccess() );
		$requests = MockSoap::getMockRequests();
		// Even with data passed, without request.body, it should use call_data
		$this->assertEquals( [ 'Get' => [ 'static' => true ] ], $requests[0]['args'] );
	}

	public function testSendWithNullData(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
			'request'       => [
				'body'   => true,
				'format' => 'json',
			],
		];

		$result = $mock->send( $config, null );

		$this->assertTrue( $result->isSuccess() );
		$requests = MockSoap::getMockRequests();
		$this->assertEquals( [ 'Get' => [] ], $requests[0]['args'] );
	}

	// ── Multiple Requests ─────────────────────────────────────────────────────

	public function testMultipleSequentialRequests(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'first' => true ], 'status' => 200 ],
			[ 'body' => [ 'second' => true ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
		];

		$mock->retrieve( $config );
		$mock->retrieve( $config );

		$requests = MockSoap::getMockRequests();
		$this->assertCount( 2, $requests );
	}

	public function testResetMockState(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'          => 'https://soap.example.com',
			'endpoint'      => '/api',
			'wsdl_mode'     => true,
			'wsdl_url'      => 'https://soap.example.com/service?wsdl',
			'soap_initiate' => 'Get',
		];

		$mock->retrieve( $config );
		$this->assertCount( 1, MockSoap::getMockRequests() );

		MockSoap::resetMockState();
		$this->assertCount( 0, MockSoap::getMockRequests() );
	}

	// ── Webservice Type ───────────────────────────────────────────────────────

	public function testSoapWebserviceTypeConstants(): void
	{
		$type = \SyncEngine\Webservice\Type\SoapWebserviceType::class;

		$this->assertEquals( 'soap', $type::TYPE );
		$this->assertEquals( 'SOAP', $type::NAME );
		$this->assertEquals( 'SOAP webservice', $type::DESCRIPTION );
		$this->assertEquals( 'none', $type::CACHE_NONE );
		$this->assertEquals( 'memory', $type::CACHE_MEMORY );
		$this->assertEquals( 'file', $type::CACHE_FILE );
		$this->assertEquals( 'both', $type::CACHE_BOTH );
		$this->assertEquals( '1.1', $type::SOAP_1_1 );
		$this->assertEquals( '1.2', $type::SOAP_1_2 );
		$this->assertEquals( 'none', $type::COMPRESSION_NONE );
		$this->assertEquals( 'gzip', $type::COMPRESSION_GZIP );
		$this->assertEquals( 'deflate', $type::COMPRESSION_DEFLATE );
	}
}
