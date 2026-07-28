<?php

namespace SyncEngine\Tests\Mock\Webservice;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Webservice\SoapMultstep;

/**
 * Mock SOAP multistep webservice for testing.
 *
 * Overrides createSoapClient() to return a MockSoapClient that captures
 * auth step calls. This allows authorizeStep() to use the parent class's
 * full logic while intercepting only the actual SOAP execution.
 *
 * Use primeMockResponses() to queue responses for each auth step.
 * Check getMockAuthRequests() to verify what was "sent".
 */
class MockSoapMultistep extends SoapMultstep
{
	public function __construct()
	{
		parent::__construct();

		$this->icon = 'webservice-soap-multistep';
		$this->name = $this->trans( 'SOAP Multistep Mock (tests)' );
		$this->description = $this->trans( 'Test-only SOAP multistep webservice with queued auth responses.' );
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
	public static function getMockAuthRequests(): array
	{
		return MockSoapClient::getRequests();
	}

	protected function createSoapClient( array $config ): \SoapClient
	{
		$wsdlUrl = $this->getWsdlUrl( $config );
		$location = $this->getLocation( $config );
		$headers = $this->setSoapHeaders( $config );
		$callOptions = $this->getSoapCallOptions( $config );
		$clientOptions = $this->getSoapClientOptions( $config );

		// Pass null as WSDL URL to avoid network calls.
		// WSDL mode info is captured via captureConfig.
		$mockClient = new MockSoapClient( null, $clientOptions );

		$mockClient->captureConfig = [
			'options'       => $clientOptions,
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
