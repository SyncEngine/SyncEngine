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
		// @todo define task fields.
		// @todo allow override or even custom auth fields.
		return [
			'endpoint' => [
				'label' => 'Endpoint',
				'type' => 'text',
			],
			'format' => [
				'label' => 'Format',
				'type' => 'select',
				'choices' => [
					// @todo other formats.
					'json' => 'JSON',
				]
			],
		];
	}

	public function getClientOptions( array $config = array() ): array
	{
		$options = [];

		$options['auto_basic'] = [ $config['username'], $config['password'] ];

		// @todo Set headers
		// @todo Set content type
		// $options['headers'] => [
		//  	'Content-Type' => 'text/plain',
		// ]

		return $options;
	}

	public function retrieve( array $config )
	{
		$client = $this->getClient( $config );
		// TODO: Implement retrieve() method.

		try {
			$url = $config['host'] . $config['endpoint'];
			$response = $client->request( 'GET', $url );

			return $response->getContent();
		} catch ( TransportExceptionInterface $e ) {
			// @todo error.
		}
	}

	public function send( array $config, $data )
	{
		$client = $this->getClient( $config );
		// TODO: Implement send() method.
	}
}
