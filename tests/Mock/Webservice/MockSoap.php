<?php

namespace SyncEngine\Tests\Mock\Webservice;

use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Tests\Mock\Client\MockSoapClient;
use SyncEngine\Webservice\Soap;

/**
 * Mock SOAP webservice for testing.
 *
 * Overrides createSoapClient() to return a MockSoapClient that captures
 * what would have been sent via the real SoapClient. This allows retrieve()
 * and send() to use the parent class's full logic (WSDL resolution, headers,
 * transport building, response decoding) while intercepting only the actual
 * SOAP execution.
 *
 * Use primeMockResponses() to queue responses, then call retrieve()/send().
 * Check getMockRequests() to verify what was "sent".
 */
class MockSoap extends Soap
{
	public function __construct()
	{
		parent::__construct();

		$this->icon = 'webservice-soap';
		$this->name = $this->trans( 'SOAP Mock (tests)' );
		$this->description = $this->trans( 'Test-only SOAP webservice with queued responses.' );
	}

	/**
	 * @param  array<int, array{body?: mixed, status?: int, soap_response?: string, soap_request?: string}>  $responses
	 */
	public static function primeMockResponses( array $responses ): void
	{
		MockSoapClient::primeResponses( $responses );
	}

	public static function resetMockState(): void
	{
		MockSoapClient::resetState();
	}

	/**
	 * @return array<int, array{method: string, args: array, headers: array, wsdl_mode: bool, wsdl_url?: string, location?: string, uri?: string, soap_version?: int, compression?: int, login?: string, password?: string, soap_action?: string, trace: bool, exceptions: bool}>
	 */
	public static function getMockRequests(): array
	{
		return MockSoapClient::getRequests();
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function getLastSoapClientState(): array
	{
		return MockSoapClient::getLastState();
	}

	public function createNativeSoapClient( array $config ): \SoapClient
	{
		return parent::createSoapClient( $config );
	}

	public function requestWithNativeSoapClient( \SoapClient $soapClient, array $config, array $args ): \SyncEngine\Webservice\Helper\Result
	{
		return $this->request( $config, $args, $soapClient );
	}

	public function executeNativeRequest( array $config, array $args ): \SyncEngine\Webservice\Helper\Result
	{
		return $this->request( $config, $args );
	}

	protected function createSoapClient( array $config ): \SoapClient
	{
		$wsdlUrl = $this->getWsdlUrl( $config );
		$location = $this->getLocation( $config );
		$headers = $this->setSoapHeaders( $config );
		$callOptions = $this->getSoapCallOptions( $config );
		$clientOptions = $this->getSoapClientOptions( $config );

		// @todo Use parent class.
		if ( ! empty( $config['wsdl_mode'] ) && empty( $wsdlUrl ) ) {
			throw new InvalidConfigException( 'A WSDL URL is required in WSDL mode.' );
		}
		if ( empty( $config['wsdl_mode'] ) && empty( $config['uri'] ) ) {
			throw new InvalidConfigException( 'A SOAP service namespace is required in non-WSDL mode.' );
		}
		if ( empty( $config['wsdl_mode'] ) && empty( $location ) ) {
			throw new InvalidConfigException( 'A SOAP endpoint is required in non-WSDL mode.' );
		}


		// Pass null as WSDL URL to avoid network calls.
		// WSDL mode info is captured via captureConfig.
		$mockClient = new MockSoapClient( null, $clientOptions );

		$mockClient->captureConfig = [
			'clientOptions' => $clientOptions,
			'callOptions'   => $callOptions,
			'wsdl_mode'     => ! empty( $config['wsdl_mode'] ),
			'wsdl_url'      => $wsdlUrl,
			'location'      => $location,
			'uri'           => $config['uri'] ?? null,
			'soap_version'  => $config['soap_version'] ?? null,
			'compression'   => $config['compression'] ?? null,
			'soap_action'   => $callOptions['soapaction'] ?? null,
			'headers'       => $headers,
			'login'         => $config['login'] ?? null,
			'password'      => $config['password'] ?? null,
		];

		return $mockClient;
	}
}
