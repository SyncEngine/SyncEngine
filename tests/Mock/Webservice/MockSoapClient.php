<?php

namespace SyncEngine\Tests\Mock\Webservice;

use SyncEngine\Service\DataFormatter;

/**
 * Mock SoapClient that captures SOAP calls and returns queued responses.
 *
 * Used by MockSoap and MockSoapMultistep to intercept SOAP execution
 * while allowing the parent webservice classes to use all their existing
 * logic (WSDL resolution, headers, transport building, response decoding).
 */
class MockSoapClient extends \SoapClient
{
	/**
	 * @var array<int, array{body?: mixed, status?: int, soap_response?: string, soap_request?: string}>
	 */
	private static array $responses = [];

	/**
	 * @var array<int, array{method: string, args: array, headers: array, wsdl_mode: bool, wsdl_url?: string, location?: string, uri?: string, soap_version?: int, compression?: int, login?: string, password?: string, soap_action?: string, trace: bool, exceptions: bool}>
	 */
	private static array $requests = [];

	/**
	 * @var array<string, mixed>
	 */
	private static array $lastSoapClientState = [];

	private ?string $lastRequest = null;

	private ?string $lastResponse = null;

	/**
	 * @var array{wsdl_mode: bool, wsdl_url?: string, location?: string, uri?: string, soap_version?: int, compression?: int, soap_action?: string, headers: array, login?: string, password?: string}|null
	 */
	public ?array $captureConfig = null;

	private ?string $wsdl = null;

	/**
	 * @var array<string, mixed>
	 */
	private array $options = [];

	/**
	 * @var array<\SoapHeader>
	 */
	private array $headers = [];

	/**
	 * Skip parent SoapClient constructor to avoid network calls and validation.
	 */
	public function __construct( mixed $wsdl, array $options = [] )
	{
		$this->wsdl = $wsdl;
		$this->options = $options;
		// No parent call - we mock everything.
	}

	/**
	 * @param  array<int, array{body?: mixed, status?: int, soap_response?: string, soap_request?: string}>  $responses
	 */
	public static function primeResponses( array $responses ): void
	{
		static::$responses = array_values( $responses );
		static::$requests = [];
		static::$lastSoapClientState = [];
	}

	public static function resetState(): void
	{
		static::$responses = [];
		static::$requests = [];
		static::$lastSoapClientState = [];
	}

	/**
	 * @return array<int, array{method: string, args: array, headers: array, wsdl_mode: bool, wsdl_url?: string, location?: string, uri?: string, soap_version?: int, compression?: int, login?: string, password?: string, soap_action?: string, trace: bool, exceptions: bool}>
	 */
	public static function getRequests(): array
	{
		return static::$requests;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function getLastState(): array
	{
		return static::$lastSoapClientState;
	}

	public function __doRequest( string $request, string $location, string $action, int $version, bool $oneWay = false ): ?string
	{
		$this->lastRequest = $request;
		return $request;
	}

	public function __getLastRequest(): ?string
	{
		return $this->lastRequest;
	}

	public function __getLastRequestHeaders(): string
	{
		return '';
	}

	public function __getLastResponse(): ?string
	{
		return $this->lastResponse;
	}

	public function __getLastResponseHeaders(): string
	{
		return '';
	}

	/**
	 * Capture headers set by the parent webservice class.
	 *
	 * @param  \SoapHeader|array<\SoapHeader>|null  $headers
	 * @return \SoapHeader[]|null
	 */
	public function __setSoapHeaders( $headers = null ): bool
	{
		if ( $headers === null ) {
			$this->headers = [];
		} else {
			$this->headers = is_array( $headers ) ? $headers : [ $headers ];
		}
		return true;
	}

	/**
	 * @param  string      $name
	 * @param  array       $args
	 * @param  array|null  $options // These are the call options, not the client options!
	 * @param              $inputHeaders
	 * @param              $outputHeaders
	 *
	 * @return mixed
	 */
	public function __soapCall( string $name, array $args, ?array $options = null, $inputHeaders = null, &$outputHeaders = null ): mixed
	{
		$response = array_shift( static::$responses ) ?? [
			'body'          => [],
			'status'        => 200,
			'soap_response' => null,
			'soap_request'  => null,
		];

		$body = $response['body'] ?? [];

		$this->lastRequest = $response['soap_request'] ?? null;

		// Build a mock SOAP envelope wrapping the response body.
		$soapResponse = $response['soap_response'] ?? null;
		if ( empty( $soapResponse ) ) {
			$soapResponse = $this->buildMockEnvelope( $body );
		} else {
			$responseBody = $soapResponse;
			// Wrap with full envelope otherwise the XML decoder won't allow the soap namespace.
			if ( str_starts_with( $responseBody, '<soap:Body>' ) ) {
				$responseBody = substr( $responseBody, strlen( '<soap:Body>' ), -strlen( '</soap:Body>' ) );
				$responseBody = $this->buildMockEnvelope( $responseBody );
			}
			$body = ( new DataFormatter() )->decode( 'xml', $responseBody );
		}
		$this->lastResponse = $soapResponse;

		// Capture the call details using the config passed during construction.
		$captureConfig = $this->captureConfig ?? [];

		static::$requests[] = [
			...$captureConfig,
			'method'        => $name,
			'args'          => $args,
			'trace'         => true,
			'exceptions'    => true,
			'headers'       => $this->extractHeaders( $this->headers ),
			'options'       => $this->options,
		];

		static::$lastSoapClientState = [
			'options'  => $this->options,
			'wsdl_url' => $captureConfig['wsdl_url'] ?? null,
		];

		return $body;
	}

	/**
	 * Build a mock SOAP envelope from a body array.
	 */
	private function buildMockEnvelope( mixed $body ): string
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>';

		if ( is_array( $body ) ) {
			$xml .= $this->arrayToXml( $body );
		} elseif ( is_string( $body ) ) {
			$xml .= $body;
		} elseif ( $body !== null ) {
			$xml .= (string) $body;
		}

		$xml .= '</soap:Body></soap:Envelope>';

		return $xml;
	}

	/**
	 * Convert array to XML string.
	 */
	private function arrayToXml( array $array, string $tag = 'item' ): string
	{
		$xml = '';
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$xml .= "<$tag>$key</$tag>" . $this->arrayToXml( $value );
			} elseif ( $value !== null ) {
				$xml .= "<$tag>$key</$tag>" . htmlspecialchars( (string) $value ) . "</$tag>";
			}
		}
		return $xml;
	}

	/**
	 * Extract header info from SoapHeader objects.
	 *
	 * @param  mixed  $headers
	 * @return array<int, array{namespace: string, key: string, value: mixed}>
	 */
	private function extractHeaders( mixed $headers ): array
	{
		if ( ! is_array( $headers ) ) {
			return [];
		}

		return array_map(
			fn( $h ) => $h instanceof \SoapHeader
				? [ 'namespace' => $h->namespace ?? '', 'key' => $h->name ?? '', 'value' => $h->data ?? '' ]
				: $h,
			$headers
		);
	}
}
