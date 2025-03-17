<?php

namespace SyncEngine\Service\Trace;

use Symfony\Contracts\HttpClient\ResponseInterface;
use SyncEngine\Service\Trace\Enum\TraceLogType;
use SyncEngine\Structure\Data\ResourceData;
use SyncEngine\Webservice\Helper\Result;

class TraceLog extends ResourceData
{
	public static function create( $resource = [], string|TraceLogType $type = 'log', TraceContext $context = null ): static
	{
		if ( $resource instanceof TraceLog ) {
			return $resource;
		}

		$instance = new static();

		if ( $resource instanceof \Throwable ) {
			$instance->setException( $resource );
		} elseif ( $resource instanceof Result ) {
			if ( $resource->getResponse() instanceof ResponseInterface ) {
				$instance->setResponse( $resource->getResponse() );
			}
			$instance->set( $resource->getDebugInfo(), 'debug' );
		} elseif ( $resource instanceof ResponseInterface ) {
			$instance->setResponse( $resource );
		} else {
			if ( $resource instanceof ResourceData ) {
				$resource = $resource->normalize();
			}

			if ( is_array( $resource ) ) {
				$instance['message'] = $resource['message'] ?? '';
				unset( $resource['message'] );
				$instance->setInfo( $resource );
			} else {
				$instance['message'] = $resource;
			}
		}

		$instance->setTimestamp();
		$instance->setType( TraceLogType::create( $type ) ?: TraceLogType::LOG );
		if ( $context ) {
			$instance->setContext( $context );
		}

		return $instance;
	}

	public function normalize( $data = null ): mixed
	{
		if ( $data ) {
			return parent::normalize( $data );
		}

		$data = $this->get();

		$exception = $this->getException();
		if ( $exception instanceof \Throwable ) {
			$exception = static::parseException( $exception );

			if ( isset( $exception['response'] ) ) {
				$this->set( $exception['response'], 'response' );
				unset( $exception['response'] );
			}

			$data['exception'] = $exception;
		}

		$response = $this->getResponse();
		if ( $response instanceof ResponseInterface ) {
			$data['response'] = static::parseResponse( $response );
		}

		if ( empty( $data['message'] ) ) {
			if ( ! empty( $data['response']['message'] ) ) {
				$data['message'] = $data['response']['message'];
			} elseif ( ! empty( $data['exception']['message'] ) ) {
				$data['message'] = $data['exception']['message'];
			}
		}

		$data['type'] = $this->getTypeString();

		return parent::normalize( $data );
	}

	public function getName(): string
	{
		return $this->getTypeString() . ' ' . $this->getTimestamp();
	}

	public function setInfo( $info ): static
	{
		if ( $info instanceof ResponseInterface ) {
			$this->setResponse( $info );
		} elseif ( $info instanceof Result ) {
			if ( $info->getResponse() instanceof ResponseInterface ) {
				$this->setResponse( $info->getResponse() );
			}
			$this->set( $info->getDebugInfo(), 'debug' );
		} elseif ( $info instanceof \Throwable ) {
			$this->setException( $info );
		} else {
			$this->set( $info, 'info' );
		}

		return $this;
	}

	public function getMessage(): ?string
	{
		return $this->get( 'message' );
	}

	public function getTimestamp( $microtime = true ): int|float
	{
		$timestamp = (float) $this->get( 'timestamp', 0 );

		return $microtime ? $timestamp : (int) $timestamp;
	}


	public function setTimestamp( float $microtime = null ): static
	{
		$this->set( $microtime ?: microtime( true ), 'timestamp' );

		return $this;
	}

	public function getContext(): null|array|TraceContext
	{
		return $this->get( 'context' );
	}

	public function setContext( TraceContext $context ): static
	{
		$this->set( $context, 'context' );

		return $this;
	}

	public function getTypeString(): ?string
	{
		return $this->getType()?->value;
	}

	public function getType(): ?TraceLogType
	{
		return TraceLogType::create( $this->get( 'type', TraceLogType::LOG ) );
	}

	public function setType( TraceLogType $type ): static
	{
		$this->set( $type, 'type' );

		return $this;
	}

	public function getResponse(): null|array|ResponseInterface
	{
		return $this->get( 'response' );
	}

	public function setResponse( ResponseInterface $response ): static
	{
		$this->set( $response, 'response' );

		return $this;
	}

	public function getException(): null|array|\Throwable
	{
		return $this->get( 'exception' );
	}

	public function setException( \Throwable $exception ): static
	{
		$this->set( $exception, 'exception' );

		return $this;
	}

	public function getFile(): string
	{
		return $this->get( 'file' );
	}

	public function setFile( string $file ): static
	{
		$this->set( $file, 'file' );

		return $this;
	}

	public function getData()
	{
		$data = $this->get( 'data' );
		if ( ! $data ) {
			$data = $this->get( 'info.data' );
		}

		return $data;
	}

	public static function parseException( \Throwable $throwable, iterable $trace = [] ): array
	{
		$trace['message'] = json_decode( $throwable->getMessage(), true ) ?? $throwable->getMessage();

		if ( method_exists( $throwable, 'getResponse' ) ) {
			$trace['response'] = $throwable->getResponse();
		}
		if ( method_exists( $throwable, 'getDebugInfo' ) ) {
			$trace['debug'] = $throwable->getDebugInfo();
		}

		$trace['line']       = $throwable->getLine();
		$trace['file']       = $throwable->getFile();
		$trace['backtrace']  = explode( "\n", $throwable->getTraceAsString() );
		$trace['_class']     = $throwable::class;

		return $trace;
	}

	public static function parseResponse( ResponseInterface $response, iterable $trace = [] ): array
	{
		if ( method_exists( $response, 'getInfo' ) ) {
			$trace['info'] = $response->getInfo();
		}
		try {
			if ( method_exists( $response, 'getHeaders' ) ) {
				$trace['headers'] = $response->getHeaders();
			}
		} catch ( \Throwable $e ) {
			$trace['headers'] = $e->getMessage();
		}
		try {
			if ( method_exists( $response, 'getContent' ) ) {
				$trace['content'] = $response->getContent( false );
			}
		} catch ( \Throwable $e ) {
			$trace['content'] = $e->getMessage();
		}

		return $trace;
	}

	public function __toString(): string
	{
		$message = $this->getMessage();
		if ( ! $message ) {
			$message = $this->normalize()['message'] ?? '';
		}
		return $message;
	}
}
