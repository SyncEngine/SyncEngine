<?php

namespace SyncEngine\Webservice\Helper;

use Symfony\Contracts\HttpClient\ResponseInterface;

readonly class BinaryResponse implements ResponseInterface
{
	public function __construct(
		private ResponseInterface $response
	) {}

	/**
	 * @inheritDoc
	 */
	public function getStatusCode(): int
	{
		return $this->response->getStatusCode();
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaders( bool $throw = true ): array
	{
		return $this->response->getHeaders( $throw );
	}

	/**
	 * @inheritDoc
	 */
	public function getContent( bool $throw = true ): string
	{
		$content = $this->response->getContent( $throw );

		return base64_encode( $content );
	}

	/**
	 * @inheritDoc
	 */
	public function toArray( bool $throw = true ): array
	{
		return $this->response->toArray( $throw );
	}

	/**
	 * @inheritDoc
	 */
	public function cancel(): void
	{
		$this->response->cancel();
	}

	/**
	 * @inheritDoc
	 */
	public function getInfo( ?string $type = null ): mixed
	{
		return $this->response->getInfo( $type );
	}
}
