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

		$this->type = 'noauth';
		$this->name = 'No Auth';
		$this->description = 'Connect without authorization';
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => 'Host',
				'type' => 'text',
			],
		];
	}

	public function getFields(): array
	{
		$fields = [
			'endpoint' => [
				'label' => 'Endpoint',
				'type' => 'text',
			],
		];

		return array_merge( parent::getFields(), $fields );
	}

	public function getClientOptions( array $config = array() ): array
	{
		$options = [];

		// @todo Set headers
		// @todo Set content type
		// $options['headers'] => [
		//  	'Content-Type' => 'text/plain',
		// ]

		return $options;
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host' ] . $config['endpoint'];
	}

	public function retrieve( array $config )
	{
		$client = $this->getClient( $config );
		// TODO: Implement retrieve() method.

		try {
			$response = $client->request( $config['method'] ?? 'GET', $this->getRequestUrl( $config ) );

			return $response->getContent();
		} catch ( TransportExceptionInterface $e ) {
			// @todo error.
		}
	}

	public function send( array $config, $data )
	{
		$client = $this->getClient( $config );
		// TODO: Implement send() method.

		try {
			$response = $client->request( $config['method'] ?? 'POST', $this->getRequestUrl( $config ), [ 'body' => $data ] );

			return $response->getContent();
		} catch ( TransportExceptionInterface $e ) {
			// @todo error.
		}
	}
}
