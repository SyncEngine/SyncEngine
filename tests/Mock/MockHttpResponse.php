<?php

namespace SyncEngine\Tests\Mock;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Mock HTTP response implementing Symfony\Contracts\HttpClient\ResponseInterface.
 *
 * Stores a pre-configured body, status code, headers and transport info.
 * Throws on 4xx/5xx when $throw is true in getHeaders/getContent/toArray.
 */
class MockHttpResponse implements ResponseInterface
{
	/**
	 * @param  string|array<int|string, mixed>  $body
	 * @param  array<string, mixed>             $info
	 */
	public function __construct(
		public string|array $body,
		public int          $httpCode = 200,
		public array        $responseHeaders = [],
		public array        $info = [],
	) {}

	public function getStatusCode(): int
	{
		return $this->httpCode;
	}

	public function getHeaders( bool $throw = true ): array
	{
		if ( $throw && $this->httpCode >= 400 ) {
			$this->throw();
		}

		// Normalize to Guzzle/Symfony format: lowercase keys, values as arrays
		$normalized = [];
		foreach ( $this->responseHeaders as $name => $value ) {
			$normalized[ strtolower( $name ) ] = is_array( $value ) ? $value : [ $value ];
		}

		return $normalized;
	}

	public function getContent( bool $throw = true ): string
	{
		if ( $throw && $this->httpCode >= 400 ) {
			$this->throw();
		}

		if ( is_array( $this->body ) ) {
			return json_encode( $this->body, JSON_THROW_ON_ERROR );
		}

		return (string) $this->body;
	}

	public function toArray( bool $throw = true ): array
	{
		if ( $throw && $this->httpCode >= 400 ) {
			$this->throw();
		}

		$content = $this->getContent( false );
		$decoded = json_decode( $content, true, flags: JSON_THROW_ON_ERROR );

		return $decoded;
	}

	public function cancel(): void
	{
		// No-op for mock
	}

	public function getInfo( ?string $type = null ): mixed
	{
		$defaults = [
			'canceled'          => false,
			'error'             => null,
			'http_code'         => $this->httpCode,
			'http_method'       => $this->info['http_method'] ?? 'GET',
			'redirect_count'    => (int) ( $this->info['redirect_count'] ?? 0 ),
			'redirect_url'      => $this->info['redirect_url'] ?? null,
			'response_headers'  => $this->responseHeaders,
			'start_time'        => (float) ( $this->info['start_time'] ?? 0.0 ),
			'url'               => $this->info['url'] ?? '',
			'user_data'         => $this->info['user_data'] ?? null,
		];

		if ( null === $type ) {
			return $defaults;
		}

		return $defaults[ $type ] ?? null;
	}

	protected function throw(): void
	{
		if ( $this->httpCode >= 500 ) {
			throw new \Symfony\Component\HttpClient\Exception\ServerException(
				$this
			);
		}

		if ( $this->httpCode >= 400 ) {
			throw new \Symfony\Component\HttpClient\Exception\ClientException(
				$this
			);
		}
	}
}
