<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Helper\ResultException;
use SyncEngine\Webservice\Trait\Http;

class NoAuth extends WebserviceModel
{
	use Http {
		getClientOptions as private getHttpClientOptions;
	}

	public function __construct()
	{
		parent::__construct();

		$this->type        = 'http';
		$this->name        = $this->trans( 'No auth', [], "webservice/noAuth" );
		$this->description = $this->trans( 'Connect without authorization', [], "webservice/noAuth" );
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => $this->trans( 'Host', [], "webservice/noAuth" ),
				'type'  => 'text',
			],
		];
	}

	public function getFields( array $defaults = [] ): array
	{
		$fields = [
			'endpoint' => [
				'label' => $this->trans( 'Endpoint', [], "webservice/noAuth" ),
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
				'label'  => $this->trans( 'Send current data', [], "webservice/noAuth" ),
				'type'   => 'switch',
				'fields' => [
					'transport' => [
						'label'      => $this->trans( 'Select data transport location', [], "webservice/noAuth" ),
						'type'       => 'select',
						'choices'    => [
							'custom'  => $this->trans( 'Manual', [], "webservice/noAuth" ),
							'body'    => $this->trans( 'Request {type}', [ 'type' => 'body' ], "webservice/noAuth" ),
							'headers' => $this->trans( 'Request {type}', [ 'type' => 'header' ], "webservice/noAuth" ),
							'query'   => $this->trans( 'Request {type}', [ 'type' => 'query' ], "webservice/noAuth" ),
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
				'label'   => $this->trans( 'Select data transport location', [], "webservice/noAuth" ),
				'type'    => 'select',
				'choices' => [
					'custom'  => $this->trans( 'Manual', [], "webservice/noAuth" ),
					'body'    => $this->trans( 'Request {type}', [ 'type' => 'body' ], "webservice/noAuth" ),
					'headers' => $this->trans( 'Request {type}', [ 'type' => 'header' ], "webservice/noAuth" ),
					'query'   => $this->trans( 'Request {type}', [ 'type' => 'query' ], "webservice/noAuth" ),
				],
			],
		];

		return array_merge( $fields, parent::getSendFields( $defaults ) );
	}

	public function connect( array $config ): Result
	{
		try {
			$result = $this->retrieve( $config );

			return new Result(
				true, true, [
				'Message' => $this->trans(
					'Successfully connected to {host}',
					[ 'host' => $this->getRequestUrl( $config ) ],
					"webservice"
				),
				'Config'  => $config,
				'Info'    => $result->getDebugInfo(),
			]
			);
		} catch ( ResultException $e ) {
			return new Result(
				false, false, [
				'Error'  => [
					'Message' => $this->trans(
						'Could not connected to {host}',
						[ 'host' => $this->getRequestUrl( $config ) ],
						"webservice"
					),
					'Error'   => $e->getMessage(),
				],
				'Config' => $config,
			]
			);
		}
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$requestConfig  = $config['request'] ?? [];
		$responseConfig = $config['response'] ?? [];

		$client = $this->getClient();
		$method = $requestConfig['method'] ?? 'GET';
		$url    = $this->getRequestUrl( $config );

		$options = $this->getClientOptions( array_replace_recursive( $config, $requestConfig ) );

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

	public function send( array $config, $data ): Result
	{
		$requestConfig  = $config['request'] ?? [];
		$responseConfig = $config['response'] ?? [];

		$client = $this->getClient();
		$method = $requestConfig['method'] ?? 'POST';
		$url    = $this->getRequestUrl( $config );

		$options = $this->getClientOptions( array_replace_recursive( $config, $requestConfig ) );

		$transport = $config['transport'] ?? 'body';

		if ( 'custom' !== $transport ) {
			if ( ! isset( $options[ $transport ] ) ) {
				$options[ $transport ] = [];
			}
			$options[ $transport ] = array_merge( $options[ $transport ], $data );
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
