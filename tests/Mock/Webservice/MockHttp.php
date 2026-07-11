<?php

namespace SyncEngine\Tests\Mock\Webservice;

use SyncEngine\Codec\File;
use SyncEngine\Service\DataFormatter;
use SyncEngine\Structure\ValueObject\Blob;
use SyncEngine\Tests\Mock\MockHttpResponse;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Http;

class MockHttp extends Http
{
	/**
	 * @var array<int, array{body?: mixed, status?: int, headers?: array, info?: array}>
	 */
	protected static array $responses = [];

	/**
	 * @var array<int, array{method: string, url: string, options: array}>
	 */
	protected static array $requests = [];

	public function __construct()
	{
		parent::__construct();

		$this->icon = 'webservice-http';
		$this->name = $this->trans( 'HTTP Mock (tests)' );
		$this->description = $this->trans( 'Test-only HTTP webservice with queued responses.' );
	}

	/**
	 * @param  array<int, array{body?: mixed, status?: int, headers?: array, info?: array}>  $responses
	 * @return void
	 */
	public static function primeMockResponses( array $responses ): void
	{
		static::$responses = array_values( $responses );
		static::$requests = [];
	}

	public static function resetMockState(): void
	{
		static::$responses = [];
		static::$requests = [];
	}

	/**
	 * @return array<int, array{method: string, url: string, options: array}>
	 */
	public static function getMockRequests(): array
	{
		return static::$requests;
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$method = $config['request']['method'] ?? 'GET';
		$url = $this->getRequestUrl( $config );

		$options = [
			'config' => $config,
		];

		if ( null !== $data ) {
			$options['data'] = $data;
		}

		// For file format responses, convert the body to a Blob like the real Http webservice
		$responseFormat = $config['response']['format'] ?? '';
		$hasFileFormat = $responseFormat === 'file';

		$result = $this->dequeueMockResult( $method, $url, $options );

		// If file format, replace the string body with a Blob
		if ( $hasFileFormat ) {
			$body = $result->getData();
			if ( is_string( $body ) ) {
				// Get response headers from debug info where dequeueMockResult stored them
				$responseDebug = $result->getDebugInfo()['response']['headers'] ?? [];
				$normalizedHeaders = [];
				foreach ( $responseDebug as $key => $value ) {
					$normalizedHeaders[ strtolower( $key ) ] = is_array( $value ) ? $value : [ $value ];
				}

				$mockResponse = new MockHttpResponse(
					$body,
					$result->getDebugInfo()['response']['status'] ?? 200,
					$normalizedHeaders
				);

				/** @var File $codec */
				$codec = ( new DataFormatter() )->getEncoder( 'file', $config['response'] ?? [] );
				$blob = $this->retrieveUpload( $mockResponse, $codec );

				$result = new Result( $blob, $result->isSuccess(), $result->getDebugInfo() );
			}
		}

		return $result;
	}

	public function send( array $config, $data ): Result
	{
		$method = $config['request']['method'] ?? 'POST';
		$url = $this->getRequestUrl( $config );

		$options = [
			'config'  => $config,
			'data'    => $data,
			'headers' => [],
		];

		// Apply the same upload preparation as the real Http webservice
		// so captured requests reflect what would actually be sent.
		if ( ! empty( $config['request']['format'] ) ) {
			if ( $this->hasBlob( $data ) ) {
				$options = $this->prepareUpload( $options, $data );
			}
		}

		return $this->dequeueMockResult( $method, $url, $options );
	}

	protected function dequeueMockResult( string $method, string $url, array $options = [] ): Result
	{
		static::$requests[] = [
			'method' => $method,
			'url' => $url,
			'options' => $options,
		];

		$response = array_shift( static::$responses ) ?? [
			'body' => [],
			'status' => 200,
			'headers' => [],
			'info' => [],
		];

		$body = $response['body'] ?? [];
		$status = (int) ( $response['status'] ?? 200 );
		$success = $status >= 200 && $status < 300;

		// If a Blob was created for file format, return it as the data
		$data = $options['blob'] ?? $body;

		return new Result(
			$data,
			$success,
			[
				'request' => $options,
				'response' => [
					'status' => $status,
					'headers' => $response['headers'] ?? [],
					'info' => $response['info'] ?? [],
				],
			]
		);
	}

	protected function hasBlob( $data ): bool
	{
		if ( $data instanceof Blob ) {
			return true;
		}
		if ( is_iterable( $data ) ) {
			foreach ( $data as $value ) {
				if ( $value instanceof Blob ) {
					return true;
				}
			}
		}
		return false;
	}
}
