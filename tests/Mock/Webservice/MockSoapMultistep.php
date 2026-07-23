<?php

namespace SyncEngine\Tests\Mock\Webservice;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\SoapMultstep;

/**
 * Mock SOAP multistep webservice for testing.
 *
 * Extends MockSoap to capture authorizeStep calls.
 * Use primeMockResponses() to queue responses for each auth step.
 */
class MockSoapMultistep extends SoapMultstep
{
	/**
	 * @var array<int, array{body?: mixed, status?: int, soap_response?: string, soap_request?: string}>
	 */
	protected static array $authResponses = [];

	/**
	 * @var array<int, array{method: string, args: array, headers: array, wsdl_mode: bool, wsdl_url?: string, location?: string, uri?: string, soap_version?: int, compression?: int, login?: string, password?: string, soap_action?: string, trace: bool, exceptions: bool}>
	 */
	protected static array $authRequests = [];

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
		static::$authResponses = array_values( $responses );
		static::$authRequests = [];
	}

	public static function resetMockState(): void
	{
		static::$authResponses = [];
		static::$authRequests = [];
	}

	/**
	 * @return array<int, array{method: string, args: array, headers: array, wsdl_mode: bool, wsdl_url?: string, location?: string, uri?: string, soap_version?: int, compression?: int, login?: string, password?: string, soap_action?: string, trace: bool, exceptions: bool}>
	 */
	public static function getMockAuthRequests(): array
	{
		return static::$authRequests;
	}

	public function authorizeStep( $authConfig, $connection ): Result
	{
		if ( ! $connection instanceof ConnectionModel ) {
			$connection = ConnectionModel::get( $connection );
		}

		$authConfigRequest = $authConfig['request'] ?? [];

		$wsdl_url = $this->getWsdlUrl( $authConfigRequest );
		$location = $this->getLocation( $authConfigRequest );
		$options  = $this->getSoapClientOptions( $authConfigRequest );

		$headers = $this->setSoapHeaders( $authConfigRequest );
		$callOptions = $this->getSoapCallOptions( $authConfigRequest );

		$method = $authConfigRequest['soap_initiate'] ?? '';
		$args   = [ $method => $authConfigRequest['call_data'] ?? [] ];

		$this->captureRequest(
			$method,
			$args,
			$headers,
			$authConfigRequest,
			$options,
			$callOptions,
			$wsdl_url,
			$location
		);

		$response = array_shift( static::$authResponses ) ?? [
			'body'          => [],
			'status'        => 200,
			'soap_response' => null,
			'soap_request'  => null,
		];

		$body = $response['body'] ?? [];
		$status = (int) ( $response['status'] ?? 200 );
		$success = $status >= 200 && $status < 300;

		// Parse auth step response for tag extraction.
		$this->parseAuthStepResponse( $body, $authConfig, $connection );

		$data = [
			'Content' => $body,
		];

		return new Result(
			$body,
			$success,
			array_merge(
				$data,
				array_filter( [
					'SoapRequest'  => $response['soap_request'] ?? null,
					'SoapResponse' => $response['soap_response'] ?? null,
				] )
			)
		);
	}

	/**
	 * Capture what would have been sent during auth step.
	 */
	protected function captureRequest(
		string $method,
		array $args,
		array $headers,
		array $config,
		array $options,
		array $callOptions,
		?string $wsdl_url,
		?string $location
	): void {
		static::$authRequests[] = [
			'method'        => $method,
			'args'          => $args,
			'headers'       => array_map(
				fn( $h ) => $h instanceof \SoapHeader
					? [ 'namespace' => $h->namespace ?? '', 'key' => $h->name ?? '' ]
					: $h,
				$headers
			),
			'wsdl_mode'     => ! empty( $config['wsdl_mode'] ),
			'wsdl_url'      => $wsdl_url,
			'location'      => $location,
			'uri'           => $options['uri'] ?? null,
			'soap_version'  => $options['soap_version'] ?? null,
			'compression'   => $options['compression'] ?? null,
			'login'         => $options['login'] ?? null,
			'password'      => $options['password'] ?? null,
			'soap_action'   => $callOptions['soapaction'] ?? null,
			'trace'         => $options['trace'] ?? false,
			'exceptions'    => $options['exceptions'] ?? false,
		];
	}
}
