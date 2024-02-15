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
		$this->name        = $this->trans( 'no_auth',[],"noAuth+intl-icu");
		$this->description = $this->trans( 'description',[],"noAuth+intl-icu");
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => $this->trans( 'Host',[],"noAuth+intl-icu" ),
				'type'  => 'text',
			],
		];
	}

	public function getFields( array $defaults = [] ): array
	{
		$fields = [
			'endpoint' => [
				'label' => $this->trans( 'Endpoint',[],"noAuth+intl-icu" ),
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
				'label' => $this->trans( 'Send_current',[],"noAuth+intl-icu" ),
				'type' => 'switch',
				'fields' => [
					'transport' => [
						'label' => $this->trans( 'Select_data_transport',[],"noAuth+intl-icu" ),
						'type' => 'select',
						'choices' => [
							'custom'  => $this->trans( 'Manual',[],"noAuth+intl-icu" ),
							'body'    => $this->trans( 'Request',['type'=>'body'],"noAuth+intl-icu" ),
							'headers' => $this->trans( 'Request',['type'=>'header'],"noAuth+intl-icu" ),
							'query'   => $this->trans( 'Request',['type'=>'query'],"noAuth+intl-icu" ),
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
				'label' => $this->trans( 'Select_data_transport',[],"noAuth+intl-icu" ),
				'type' => 'select',
				'choices' => [
					'custom'  => $this->trans( 'Manual',[],"noAuth+intl-icu" ),
					'body'    => $this->trans( 'Request',['type'=>'body'],"noAuth+intl-icu" ),
					'headers' => $this->trans( 'Request',['type'=>'header'],"noAuth+intl-icu" ),
					'query'   => $this->trans( 'Request',['type'=>'query'],"noAuth+intl-icu" ),
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

		$response = $client->request( $method, $url, $options );

		$content = $response->getContent();

		if ( ! empty( $responseConfig['format'] ) ) {
			$content = $this->decodeFormat( $responseConfig['format'], $content );
		}

		return new Result( $content, $response, $options );
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

		$response = $client->request( $method, $url, $options );

		$content = $response->getContent();

		if ( ! empty( $responseConfig['format'] ) ) {
			$content = $this->decodeFormat( $responseConfig['format'], $content );
		}

		return new Result( $content, $response, $options );
	}
}
