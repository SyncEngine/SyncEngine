<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use mysql_xdevapi\Exception;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Rest extends WebserviceModel
{
	public function __construct()
	{
		$this->type = 'rest';
		$this->name = 'REST';
		$this->description = 'Connect to a REST API';

		parent::__construct();
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => 'Host',
				'type' => 'text',
			],
			'auth_type' => [
				'label' => 'Auth type',
				'type' => 'select',
				'choices'  => [
					'none' => 'None',
					'password' => 'Password',
					'key' => 'Key/Secret',
					'token' => 'Token',
				],
			],
			'username' => [
				'label' => 'Username',
				'type' => 'text',
				'conditionals' => [
					'auth_type' => 'password'
				],
			],
			'password' => [
				'label' => 'Password',
				'type' => 'password',
				'conditionals' => [
					'auth_type' => 'password'
				],
			],
			'token' => [
				'label' => 'Token',
				'type' => 'password',
				'conditionals' => [
					'auth_type' => 'token'
				],
			],
			'key' => [
				'label' => 'Key',
				'type' => 'password',
				'conditionals' => [
					'auth_type' => 'key'
				],
			],
			'secret' => [
				'label' => 'Secret',
				'type' => 'password',
				'conditionals' => [
					'auth_type' => 'key'
				],
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
			]
		];
	}

	public function getClientOptions( array $config = array() ): array
	{
		$options = [];

		switch ( $config['auth_type'] ) {
			case 'password':
				$options['auto_basic'] = [ $config['username'], $config['password'] ];
			break;
			case 'key':
				$options['auto_basic'] = [ $config['key'], $config['secret'] ];
			break;
			case 'token':
				$options['query'] = [
					'token' => $config['token'],
				];
			break;
		}

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
