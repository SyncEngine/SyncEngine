<?php

namespace SyncEngine\Tests\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Tests\Mock\Webservice\MockSoap;
use SyncEngine\Tests\TestCase\BaseTestCase;
use SyncEngine\Webservice\Helper\Result;

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
		$this->assertEquals( 1, $requests[0]['exception'] );
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
		$this->assertEquals( SOAP_COMPRESSION_GZIP, $requests[0]['compression'] );
	}

	public function testCompressionBothOption(): void
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
			'compression'   => 'both',
		];

		$mock->retrieve( $config );

		$requests = MockSoap::getMockRequests();
		$this->assertEquals( SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE, $requests[0]['compression'] );
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

	// ── Cache Fields ───────────────────────────────────────────────────────────
	// NOT IMPLEMENTED YET

	/*public function testWsdLCachingOption(): void
	{
		$mock = $this->getMockSoap();
		$mock::primeMockResponses( [
			[ 'body' => [ 'ok' ], 'status' => 200 ],
		] );

		$config = [
			'host'           => 'https://soap.example.com',
			'endpoint'       => '/api',
			'wsdl_mode'      => true,
			'wsdl_url'       => 'https://soap.example.com/service?wsdl',
			'soap_initiate'  => 'Get',
			'cache_wsdl'     => 'file',
			'cache_wsdl_dir' => '/tmp/wsdl-cache',
		];

		$mock->retrieve( $config );

		$state = MockSoap::getLastSoapClientState();
		$this->assertEquals( 1, $state['options']['cache_wsdl'] ); // WSDL_CACHE_DISK = 1
		$this->assertEquals( '/tmp/wsdl-cache', $state['options']['cache_wsdl_dir'] );
	}

	public function testWsdLCacheNoneOption(): void
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
			'cache_wsdl'    => 'none',
		];

		$mock->retrieve( $config );

		$state = MockSoap::getLastSoapClientState();
		$this->assertEquals( 0, $state['options']['cache_wsdl'] ); // WSDL_CACHE_NONE = 0
	}*/

	// ── Auth Fields ───────────────────────────────────────────────────────────
	// NOT IMPLEMENTED YET

	/*public function testAuthFieldsHaveUsernameAndPassword(): void
	{
		$mock = $this->getMockSoap();
		$fields = $mock->getAuthFields();

		$this->assertNotNull( $fields->get( 'host' ) );
		$this->assertNotNull( $fields->get( 'username' ) );
		$this->assertNotNull( $fields->get( 'password' ) );

		$usernameField = $fields->get( 'username' );
		$passwordField = $fields->get( 'password' );

		$this->assertEquals( 'secret', $usernameField['type'] );
		$this->assertEquals( 'secret', $passwordField['type'] );
	}

	public function testLoginPasswordPassedToSoapClient(): void
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
			'username'      => 'testuser',
			'password'      => 'secret123',
		];

		$mock->retrieve( $config );

		$state = MockSoap::getLastSoapClientState();
		$this->assertEquals( 'testuser', $state['options']['login'] );
		$this->assertEquals( 'secret123', $state['options']['password'] );
	}*/

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
		// Verify SOAPAction header was added
		$this->assertCount( 1, $requests[0]['headers'] );
		$this->assertEquals( 'SOAPAction', $requests[0]['headers'][0]['key'] );
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

		// Even with data passed, without request.body, it should use call_data
		$result = $mock->send( $config, [ 'dynamic' => true ] );

		$this->assertTrue( $result->isSuccess() );
		$requests = MockSoap::getMockRequests();
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
		// With null data and body config, should fall back to call_data
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
		$this->assertEquals( 'loader', $type::CACHE_LOADER );
		$this->assertEquals( 'file', $type::CACHE_FILE );
		$this->assertEquals( 'auto', $type::CACHE_AUTO );
		$this->assertEquals( '1.1', $type::SOAP_1_1 );
		$this->assertEquals( '1.2', $type::SOAP_1_2 );
		$this->assertEquals( 'none', $type::COMPRESSION_NONE );
		$this->assertEquals( 'gzip', $type::COMPRESSION_GZIP );
		$this->assertEquals( 'deflate', $type::COMPRESSION_DEFLATE );
		$this->assertEquals( 'both', $type::COMPRESSION_BOTH );
	}
}
