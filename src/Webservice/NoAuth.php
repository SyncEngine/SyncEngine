<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use App\Webservice\Trait\Http;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NoAuth extends WebserviceModel
{
	use Http {
		getClientOptions as private getHttpClientOptions;
	}

	public function __construct()
	{
		parent::__construct();

		$this->type        = 'http';
		$this->name        = 'No Auth';
		$this->description = 'Connect without authorization';
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => 'Host',
				'type'  => 'text',
			],
		];
	}

	public function getFields(): array
	{
		$fields = [
			'endpoint' => [
				'label' => 'Endpoint',
				'type'  => 'text',
			],
		];

		return array_merge( $fields, parent::getFields() );
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

	public function retrieve( array $config )
	{
		$requestConfig  = $config['request'] ?? [];
		$responseConfig = $config['response'] ?? [];

		$client  = $this->getClient();
		$method  = $requestConfig['method'] ?? 'GET';
		$url     = $this->getRequestUrl( $config );
		$options = $this->getClientOptions( array_merge( $config, $requestConfig ) );

		$response = $client->request( $method, $url, $options );

		$content = $response->getContent();

		return $this->fromFormat( $responseConfig['format'] ?? '', $content );
	}

	public function send( array $config, $data )
	{
		$requestConfig = $config['request'] ?? [];

		$client = $this->getClient();
		$method = $requestConfig['method'] ?? 'POST';
		$url    = $this->getRequestUrl( $config );

		$data            = $this->toFormat( $requestConfig['format'] ?? '', $data );
		$options         = $this->getClientOptions( array_merge( $config, $requestConfig ) );
		$options['body'] = $data;

		$response = $client->request( $method, $url, $options );

		// @todo Implement return handler.
		return $response->getContent();
	}
}
