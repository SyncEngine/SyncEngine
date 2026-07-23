<?php

namespace SyncEngine\Tests\Mock\Webservice;

use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Soap;

/**
 * Mock SOAP webservice for testing.
 *
 * Bypasses the real SoapClient and captures what would have been sent.
 * Use primeMockResponses() to queue responses, then call retrieve()/send().
 * Check getMockRequests() to verify what was "sent".
 */
class MockSoap extends Soap
{
	/**
	 * @var array<int, array{body?: mixed, status?: int, soap_response?: string, soap_request?: string}>
	 */
	protected static array $responses = [];

	/**
	 * @var array<int, array{method: string, args: array, headers: array, wsdl_mode: bool, wsdl_url?: string, location?: string, uri?: string, soap_version?: int, compression?: int, login?: string, password?: string, soap_action?: string, trace: bool, exceptions: bool}>
	 */
	protected static array $requests = [];

	/**
	 * @var array<string, mixed>
	 */
	protected static array $lastSoapClientState = [];

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
		static::$responses = array_values( $responses );
		static::$requests = [];
		static::$lastSoapClientState = [];
	}

	public static function resetMockState(): void
	{
		static::$responses = [];
		static::$requests = [];
		static::$lastSoapClientState = [];
	}

	/**
	 * @return array<int, array{method: string, args: array, headers: array, wsdl_mode: bool, wsdl_url?: string, location?: string, uri?: string, soap_version?: int, compression?: int, login?: string, password?: string, soap_action?: string, trace: bool, exceptions: bool}>
	 */
	public static function getMockRequests(): array
	{
		return static::$requests;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function getLastSoapClientState(): array
	{
		return static::$lastSoapClientState;
	}

	public function createNativeSoapClient( array $config ): \SoapClient
	{
		return $this->createSoapClient( $config );
	}

	public function requestWithNativeSoapClient( \SoapClient $soapClient, array $config, array $args ): Result
	{
		return $this->request( $config, $args, $soapClient );
	}

	public function executeNativeRequest( array $config, array $args ): Result
	{
		return $this->request( $config, $args );
	}

	public function resolveWsdlCacheDirectory( array $config, string $wsdlUrl ): string
	{
		return $this->getWsdlCacheDirectory( $config, $wsdlUrl );
	}

	public function prepareResolvedWsdlCacheDirectory( string $cacheDirectory ): void
	{
		$this->prepareWsdlCacheDirectory( $cacheDirectory );
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$wsdl_url = $this->getWsdlUrl( $config );
		$location = $this->getLocation( $config );
		$options  = $this->getSoapClientOptions( $config );

		$headers = $this->setSoapHeaders( $config );
		$callOptions = $this->getSoapCallOptions( $config );

		$method = $config['soap_initiate'] ?? '';
		$args   = [ $method => $config['call_data'] ?? [] ];

		$this->captureRequest( $method, $args, $headers, $config, $options, $callOptions, $wsdl_url, $location );

		$response = array_shift( static::$responses ) ?? [
			'body'         => [],
			'status'       => 200,
			'soap_response' => null,
			'soap_request' => null,
		];

		$body = $response['body'] ?? [];
		$status = (int) ( $response['status'] ?? 200 );
		$success = $status >= 200 && $status < 300;

		// Apply response format decoding if configured.
		$decodedResult = $body;
		if ( ! empty( $config['response']['format'] ) ) {
			$rawResponse = $response['soap_response'] ?? null;
			if ( ! empty( $rawResponse ) ) {
				$codec = ( new \SyncEngine\Service\DataFormatter() )->getEncoder(
					$config['response']['format'],
					$config['response']
				);
				if ( $codec ) {
					$decodedResult = $this->decodeFormat( $codec, $rawResponse, $config['response'] );
				}
			}
		}

		return new Result(
			$decodedResult,
			$success,
			[
				'SoapRequest'  => $response['soap_request'] ?? null,
				'SoapResponse' => $response['soap_response'] ?? null,
			]
		);
	}

	public function send( array $config, $data ): Result
	{
		$wsdl_url = $this->getWsdlUrl( $config );
		$location = $this->getLocation( $config );
		$options  = $this->getSoapClientOptions( $config );

		$headers = $this->setSoapHeaders( $config );
		$callOptions = $this->getSoapCallOptions( $config );

		$method = $config['soap_initiate'] ?? '';

		// If body is configured, encode $data using the request format codec.
		if ( ! empty( $config['request']['body'] ) && $data !== null ) {
			$format = $config['request']['format'] ?? null;
			if ( $format ) {
				$encoded = $this->encodeFormat( $format, $data, $config['request'] );
				$args    = [ $method => $encoded ];
			} else {
				$args = [ $method => $data ];
			}
		} else {
			$args = [ $method => $config['call_data'] ?? [] ];
		}

		$this->captureRequest( $method, $args, $headers, $config, $options, $callOptions, $wsdl_url, $location );

		$response = array_shift( static::$responses ) ?? [
			'body'         => [],
			'status'       => 200,
			'soap_response' => null,
			'soap_request' => null,
		];

		$body = $response['body'] ?? [];
		$status = (int) ( $response['status'] ?? 200 );
		$success = $status >= 200 && $status < 300;

		// Apply response format decoding if configured.
		$decodedResult = $body;
		if ( ! empty( $config['response']['format'] ) ) {
			$rawResponse = $response['soap_response'] ?? null;
			if ( ! empty( $rawResponse ) ) {
				$codec = ( new \SyncEngine\Service\DataFormatter() )->getEncoder(
					$config['response']['format'],
					$config['response']
				);
				if ( $codec ) {
					$decodedResult = $this->decodeFormat( $codec, $rawResponse, $config['response'] );
				}
			}
		}

		return new Result(
			$decodedResult,
			$success,
			[
				'SoapRequest'  => $response['soap_request'] ?? null,
				'SoapResponse' => $response['soap_response'] ?? null,
			]
		);
	}

	/**
	 * Capture what would have been sent to the real SoapClient.
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
		static::$requests[] = [
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

		static::$lastSoapClientState = [
			'options' => $options,
			'wsdl_url' => $wsdl_url,
			'location' => $location,
		];
	}
}
