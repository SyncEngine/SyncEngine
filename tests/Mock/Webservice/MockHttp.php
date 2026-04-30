<?php

namespace SyncEngine\Tests\Mock\Webservice;

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

		return $this->dequeueMockResult( $method, $url, $options );
	}

	public function send( array $config, $data ): Result
	{
		$method = $config['request']['method'] ?? 'POST';
		$url = $this->getRequestUrl( $config );

		$options = [
			'config' => $config,
			'data' => $data,
		];

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

		return new Result(
			$body,
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
}
