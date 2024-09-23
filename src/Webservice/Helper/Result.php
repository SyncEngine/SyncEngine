<?php

namespace SyncEngine\Webservice\Helper;

use Masterminds\HTML5\Parser\UTF8Utils;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Result
{
	public function __construct(
		public mixed $data = null, public mixed $response = true, public array $debugInfo = []
	) {}

	public function isSuccessful()
	{
		return $this->isSuccess();
	}

	public function isSuccess()
	{
		$response = $this->getResponse();

		if ( is_object( $response ) ) {
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

		return ! empty( $this->response );
	}

	public function getResponse(): mixed
	{
		return $this->response;
	}

	public function getDebugResponse(): JsonResponse
	{
		if ( $this->isException() ) {
			$return = $this->getExceptionResponseData();
		} else {
			$return = [
				'success'  => $this->isSuccess(),
				'response' => $this->getResponse(),
				'result'   => $this->getData(),
				'data'     => $this->getDebugInfo(),
			];
		}
		$status = $return['response'] instanceof ResponseInterface ? $return['response']->getStatusCode() : null;

		$json  = json_encode( $return );
		if ( json_last_error() === JSON_ERROR_UTF8 ) {
			$json = json_encode( $this->encode_utf8( $return ) );
		}

		return new JsonResponse( $json, $status ?? 200, [], true );
	}

	public function encode_utf8( $data )
	{
		foreach ( $data as $n => $v ) {
			if ( is_array( $v ) ) {
				$data[ $n ] = $this->encode_utf8( $v );
			} elseif ( is_string( $v ) ) {
				$data[ $n ] = UTF8Utils::convertToUTF8( $v );
			}
		}

		return $data;
	}

	public function isException(): bool
	{
		return $this->response instanceof \Throwable;
	}

	public function getExceptionResponseData(): array
	{
		if ( ! $this->isException() ) {
			return [];
		}

		/** @var \Throwable $exception */
		$exception = $this->response;

		$message = [
			'Message'   => $exception->getMessage(),
			'Exception' => $exception::class,
		];

		$data = $this->getInfo();

		$data['Message'] = array_merge( $data['Message'] ?? [], $message );

		if ( $exception instanceof ClientException ) {
			$response = $exception->getResponse();
		}

		if ( isset( $response ) && $response instanceof ResponseInterface ) {
			$data['Content'] = $response->getContent( false );
			$data['Headers'] = $response->getHeaders( false );
			$data['Info']    = $response->getInfo();
		}

		return [
			'success'  => false,
			'response' => $response ?? null,
			'data'     => $data,
		];
	}

	public function getData()
	{
		return $this->data;
	}

	public function getDebugInfo()
	{
		if ( ! $this->debugInfo ) {
			return $this->data;
		}

		return $this->debugInfo;
	}

	public function getInfo(): array
	{
		$response = $this->getResponse();

		if ( is_object( $response ) ) {
			if ( method_exists( $response, 'getInfo' ) ) {
				return (array) $response->getInfo();
			}
			if ( isset( $response->info ) ) {
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

	public function __call( string $name, array $arguments )
	{
		if ( ! is_callable( [ $this->response, $name ] ) ) {

			throw new \ErrorException(
				$this->trans(
					'Method not found: {name}',
					[ 'name' => __CLASS__ . '::' . $name ]
				)
			);
		}

		return call_user_func_array( [ $this->response, $name ], $arguments );
	}
}
