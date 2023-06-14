<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use mysql_xdevapi\Exception;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Basic extends WebserviceModel
{
	public function __construct()
	{
		$this->type = 'basic';
		$this->name = 'Basic';
		$this->description = 'Connect with basic authorization';

		parent::__construct();
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => 'Host',
				'type' => 'text',
			],
			'username' => [
				'label' => 'Username',
				'type' => 'text',
			],
			'password' => [
				'label' => 'Password',
				'type' => 'password',
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

		$options['auth_basic'] = [ $config['username'], $config['password'] ];

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

		try {
			$response = $client->request( 'GET', $this->getRequestUrl( $config ) );

			$content = $response->getContent();

			return $this->fromFormat( $config['format'], $content );
		} catch ( TransportExceptionInterface $e ) {
			// @todo error.
		}
	}

	public function send( array $config, $data )
	{
		$client = $this->getClient( $config );

		try {
			$data = $this->toFormat( $config['format'], $data );

			$response = $client->request( 'POST', $this->getRequestUrl( $config ), [ 'body' => $data ] );

			// @todo Implement return handler.
			return $response->getContent();
		} catch ( TransportExceptionInterface $e ) {
			// @todo error.
		}
	}
}
