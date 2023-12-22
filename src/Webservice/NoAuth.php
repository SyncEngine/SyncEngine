<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
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
		$this->name        = $this->trans( 'No Auth' );
		$this->description = $this->trans( 'Connect without authorization' );
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
			'send' => [
				'label' => $this->trans( 'Send current data?' ),
				'type' => 'switch',
				'fields' => [
					'transport' => [
						'label' => $this->trans( 'Select data transport location' ),
						'type' => 'select',
						'choices' => [
							'query'   => $this->trans( 'Request Query' ),
							'headers' => $this->trans( 'Request Headers' ),
							'body'    => $this->trans( 'Request Body' ),
						],
						'conditions' => [
							'send' => true,
						],
					],
				]
			],
		];

		return array_merge( $fields, parent::getSendFields( $defaults ) );
	}

	public function getSendFields( array $defaults = [] ): array
	{
		$fields = [
			'endpoint' => [], // Override order.
			'transport' => [
				'label' => $this->trans( 'Select data transport location' ),
				'type' => 'select',
				'choices' => [
					'query'   => $this->trans( 'Request Query' ),
					'headers' => $this->trans( 'Request Headers' ),
					'body'    => $this->trans( 'Request Body' ),
				],
			],
		];

		return array_merge( $fields, parent::getSendFields( $defaults ) );
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

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] . ( $config['endpoint'] ?? '' );
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$requestConfig  = $config['request'] ?? [];
		$responseConfig = $config['response'] ?? [];

		$client  = $this->getClient();
		$method  = $requestConfig['method'] ?? 'GET';
		$url     = $this->getRequestUrl( $config );

		$transport = [];

		$options = $this->getClientOptions( array_replace_recursive( $config, $requestConfig ) );

		if ( ! empty( $config['send'] ) && $data ) {
			$transport = $config['transport'] ?? 'body';

			if ( ! empty( $requestConfig['format'] ) ) {
				$options[ $transport ] = $this->encodeFormat( $requestConfig['format'], $data );;
			} else {
				$options[ $transport ] = array_merge( $options[ $transport ] ?? [], $data );
			}
		}

		$response = $client->request( $method, $url, $options );

		$content = $response->getContent();

		if ( ! empty( $responseConfig['format'] ) ) {
			$content = $this->decodeFormat( $responseConfig['format'], $content );
		}

		return new Result( $content, $response );
	}

	public function send( array $config, $data ): Result
	{
		$requestConfig = $config['request'] ?? [];

		$client = $this->getClient();
		$method = $requestConfig['method'] ?? 'POST';
		$url    = $this->getRequestUrl( $config );

		$options = $this->getClientOptions( array_replace_recursive( $config, $requestConfig ) );

		$transport = $config['transport'] ?? 'body';

		if ( ! empty( $requestConfig['format'] ) ) {
			$options[ $transport ] = $this->encodeFormat( $requestConfig['format'], $data );;
		} else {
			if ( ! isset( $options[ $transport ] ) ) {
				$options[ $transport ] = [];
			}
			$options[ $transport ] = array_merge( $options[ $transport ], $data );
		}

		$response = $client->request( $method, $url, $options );

		// @todo Implement return handler.
		return new Result( $response->getContent(), $response );
	}
}
