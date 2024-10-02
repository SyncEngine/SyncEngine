<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Exception\ResultException;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Trait\ClientHttp;
use SyncEngine\Webservice\Type\HttpWebserviceType;

class NoAuth extends WebserviceModel
{
	use ClientHttp {
		getClientOptions as private getHttpClientOptions;
	}

	public function __construct()
	{
		parent::__construct();

		$this->type        = HttpWebserviceType::TYPE;
		$this->name        = $this->trans( 'HTTP No auth' );
		$this->description = $this->trans( 'Connect without authorization' );
	}

	/**
	 * @inheritDoc
	 */
	public function getConnectFields(): array
	{
		return array_merge( parent::getConnectFields(), $this->getFields() );
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => $this->trans( 'Host' ),
				'type'  => 'text',
			],
		];
	}

	public function getFields( array $defaults = [] ): array
	{
		$fields = [
			'endpoint' => [
				'label' => $this->trans( 'Endpoint' ),
				'type'  => 'text',
			],
		];

		return array_merge( $fields, parent::getFields( $defaults ) );
	}

	public function getRetrieveFields( array $defaults = [] ): array
	{
		$fields = [
			'endpoint' => [], // Override order.
			'send'     => [
				'label'  => $this->trans( 'Send current data' ),
				'type'   => 'switch',
				'fields' => [
					'transport' => [
						'label'      => $this->trans( 'Select data transport location' ),
						'type'       => 'select',
						'choices'    => [
							'custom'  => $this->trans( 'Manual' ),
							'body'    => $this->trans( 'Request {type}', [ 'type' => 'body' ] ),
							'headers' => $this->trans( 'Request {type}', [ 'type' => 'header' ] ),
							'query'   => $this->trans( 'Request {type}', [ 'type' => 'query' ] ),
						],
						'conditions' => [
							'send' => true,
						],
					],
				],
			],
		];

		return array_merge( $fields, parent::getSendFields( $defaults ) );
	}

	public function getSendFields( array $defaults = [] ): array
	{
		$fields = [
			'endpoint'  => [], // Override order.
			'transport' => [
				'label'   => $this->trans( 'Select data transport location' ),
				'type'    => 'select',
				'choices' => [
					'custom'  => $this->trans( 'Manual' ),
					'body'    => $this->trans( 'Request {type}', [ 'type' => 'body' ] ),
					'headers' => $this->trans( 'Request {type}', [ 'type' => 'header' ] ),
					'query'   => $this->trans( 'Request {type}', [ 'type' => 'query' ] ),
				],
			],
		];

		return array_merge( $fields, parent::getSendFields( $defaults ) );
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] . ( $config['endpoint'] ?? '' );
	}

	public function getClientOptions( array $config = [] ): array
	{
		$options = [];

		// @todo Set content type
		// $options['headers'] => [
		//  	'Content-Type' => 'text/plain',
		// ]

		return array_merge_recursive( $this->getHttpClientOptions( $config ), $options );
	}

	public function connect( array $config ): Result
	{
		try {
			$result = $this->retrieve( $config );

			$debugInfo = [
				'Success' => [
					'Message' => $this->trans(
						'Successfully connected to {host}',
						[ 'host' => $this->getRequestUrl( $config ) ]
					),
					'Result'  => $result->getData(),
				],
				'Config'  => $config,
				'Info'    => $result->getDebugInfo(),
			];

			return new Result( true, true, $debugInfo );

		} catch ( \Exception $e ) {
			if ( ! $e instanceof ResultException ) {
				// @todo Pass debug info from exception?
				$e = new ResultException( $e );
			}

			$debugInfo = [
				'Error'    => [
					'Message' => $this->trans(
						'Could not connected to {host}',
						[ 'host' => $this->getRequestUrl( $config ) ]
					),
					'Error' => $e->getMessage(),
				],
				'Info'     => $e->getDebugInfo(),
				'Response' => $e->getResponse(),
				'Config'   => $config,
			];

			return new Result( false, false, $debugInfo );
		}
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$requestConfig  = $config['request'] ?? [];
		$responseConfig = $config['response'] ?? [];

		$client = $this->getClient();
		$method = $requestConfig['method'] ?? 'GET';
		$url    = $this->getRequestUrl( $config );

		$options = $this->getClientOptions(
			array_replace_recursive( $config, $requestConfig, [ 'method' => $method ] )
		);

		if ( ! empty( $config['send'] ) && $data ) {
			$transport = $config['transport'] ?? '';

			if ( 'custom' !== $transport ) {
				$options[ $transport ] = array_merge( $options[ $transport ] ?? [], $data );
			}
		}

		if ( ! empty( $requestConfig['format'] ) && ! empty( $options['body'] ) ) {
			$options['body'] = $this->encodeFormat( $requestConfig['format'], $options['body'] );
		}

		try {
			$response = $client->request( $method, $url, $options );

			$content = $response->getContent();

			if ( ! empty( $responseConfig['format'] ) ) {
				$content = $this->decodeFormat( $responseConfig['format'], $content );
			}

			return new Result( $content, $response, $options );
		} catch ( \Throwable $e ) {
			throw new ResultException( $e, [ 'request' => $options ] );
		}
	}

	public function send( array $config, $data ): Result
	{
		$requestConfig  = $config['request'] ?? [];
		$responseConfig = $config['response'] ?? [];

		$client = $this->getClient();
		$method = $requestConfig['method'] ?? 'POST';
		$url    = $this->getRequestUrl( $config );

		$options = $this->getClientOptions(
			array_replace_recursive( $config, $requestConfig, [ 'method' => $method ] )
		);

		$transport = $config['transport'] ?? 'body';

		if ( $data && 'custom' !== $transport ) {
			if ( is_iterable( $data ) ) {
				if ( ! isset( $options[ $transport ] ) ) {
					$options[ $transport ] = [];
				}
				$options[ $transport ] = array_merge( $options[ $transport ], $data );
			} else {
				$options[ $transport ] = $data;
			}
		}

		if ( ! empty( $requestConfig['format'] ) && ! empty( $options['body'] ) ) {
			$options['body'] = $this->encodeFormat( $requestConfig['format'], $options['body'] );
		}

		try {
			$response = $client->request( $method, $url, $options );

			$content = $response->getContent();

			if ( ! empty( $responseConfig['format'] ) ) {
				$content = $this->decodeFormat( $responseConfig['format'], $content );
			}

			return new Result( $content, $response, $options );
		} catch ( \Throwable $e ) {
			throw new ResultException( $e, [ 'request' => $options ] );
		}
	}
}
