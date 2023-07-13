<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use mysql_xdevapi\Exception;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NoAuth extends WebserviceModel
{
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

		return array_merge_recursive( parent::getClientOptions( $config ), $options );
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] . ( $config['endpoint'] ?? '' );
	}

	public function retrieve( array $config )
	{
		$requestConfig = $config['request'];
		$responseConfig = $config['response'];

		try {
			$client   = $this->getClient();
			$response = $client->request( $requestConfig['method']
			                              ??
			                              'GET', $this->getRequestUrl( $config ), $this->getClientOptions( array_merge( $config, $requestConfig ) ) );

			$content = $response->getContent();

			return $this->fromFormat( $responseConfig['format'] ?? '', $content );
		} catch ( TransportExceptionInterface $e ) {
			return [ "error" => $e->getMessage() ];
		}
	}

	public function send( array $config, $data )
	{
		$requestConfig = $config['request'];

		try {
			$data = $this->toFormat( $requestConfig['format'] ?? '', $data );

			$options         = $this->getClientOptions( array_merge( $config, $requestConfig ) );
			$options['body'] = $data;

			$client   = $this->getClient();
			$response = $client->request( $requestConfig['method'] ?? 'POST', $this->getRequestUrl( $config ), $options );

			// @todo Implement return handler.
			return $response->getContent();
		} catch ( TransportExceptionInterface $e ) {
			return [ "error" => $e->getMessage() ];
		}
	}
}
