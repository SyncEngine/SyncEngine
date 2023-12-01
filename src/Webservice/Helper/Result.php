<?php

namespace App\Webservice\Helper;

class Result
{
	public function __construct(
		public mixed $data = null,
		public mixed $response = null,
		public ?bool $success = null,
		public array $headers = [],
		public array $info = []
	) {
	}

	public function isSuccessful()
	{
		return $this->isSuccess();
	}

	public function isSuccess()
	{
		if ( null === $this->success && $this->response ) {
			$response = $this->response;
			if ( is_callable( [ $response, 'isSuccess' ] ) ) {
				return $response->isSuccess();
			}
			if ( is_callable( [ $response, 'isSuccessful' ] ) ) {
				return $response->isSuccessful();
			}
			if ( is_callable( [ $response, 'isOk' ] ) ) {
				return $response->isOk();
			}
		}
		return $this->success ?? false;
	}

	public function getData()
	{
		return $this->data;
	}

	public function getInfo(): array
	{
		if ( $this->info ) {
			return $this->info;
		}
		if ( $this->response ) {
			$response = $this->response;
			if ( is_callable( [ $response, 'getInfo' ] ) ) {
				return (array) $response->getInfo();
			}
			if ( $response->info ) {
				if ( is_array( $response->info ) ) {
					return $response->info;
				}
				if ( is_callable( $response->info, 'all' ) ) {
					return $this->info->all();
				}
			}
		}
		return [];
	}

	public function getHeaders(): array
	{
		if ( $this->headers ) {
			return $this->headers;
		}
		if ( $this->response ) {
			$response = $this->response;
			if ( is_callable( [ $response, 'getHeaders' ] ) ) {
				return (array) $response->getHeaders();
			}
			if ( $response->headers ) {
				if ( is_array( $response->headers ) ) {
					return $response->headers;
				}
				if ( is_callable( $response->headers, 'all' ) ) {
					return $this->headers->all();
				}
			}
		}
		return [];
	}

	public function __call( string $name, array $arguments )
	{
		if ( ! is_callable( [ $this->response, $name ] ) ) {
			throw new \Exception( 'Method not found: ' . __CLASS__ . '::' . $name );
		}

		return call_user_func_array( [ $this->response, $name ], $arguments );
	}
}
