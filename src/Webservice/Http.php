<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use mysql_xdevapi\Exception;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Http extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type = 'http';
		$this->name = 'HTTP';
		$this->description = 'Connect to an HTTP server to send/retrieve data.';
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => 'Host',
				'type' => 'text',
			],
			'authorization' => [
				'label'    => 'Authorization',
				'fieldset' => array_merge(
					parent::getHttpFields(),
					parent::getFormatFields(),
					array(
						'url' => [
							'label' => 'Url',
							'help' => 'The URL for this authentication step',
							'type' => 'text',
						],
						'response' => [
							'label' => 'Response',
							'help' => 'The type of response the URL will return',
							'type' => 'select',
							'choices' => [
								'header'   => 'Header',
								'body'     => 'Body',
								'redirect' => 'Redirect',
				'type'     => 'repeatable',
							],
						],
						'response_param' => [
							'label' => 'Response param name',
							'help' => 'The param name where the authentication parameters are located',
							'type' => 'text',
							'placeholder' => 'token',
						],
						'tag' => [
							'label' => 'Storage tag to be used in next auth steps',
							'help' => 'Choose the tag name in which the response param value is stored',
							'type' => 'text',
							'placeholder' => 'token',
						],
						'tag_expiration' => [
							// @todo Duration picker.
							'label' => 'Storage tag expiration in hours',
							'help' => 'Set a expiration timer for the tag value so re-authentication will done within this expiration timeframe',
							'type' => 'number',
						],
					)
				),
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
		try {
			$client = $this->getClient();
			$response = $client->request( $config['method'] ?? 'GET', $this->getRequestUrl( $config ), $this->getClientOptions( $config ) );

			$content = $response->getContent();

			return $this->fromFormat( $config['format'], $content );
		} catch ( TransportExceptionInterface $e ) {
			// @todo error.
		}
	}

	public function send( array $config, $data )
	{
		try {
			$data = $this->toFormat( $config['format'], $data );

			$options = $this->getClientOptions( $config );
			$options['body'] = $data;

			$client = $this->getClient();
			$response = $client->request( $config['method'] ?? 'POST', $this->getRequestUrl( $config ), $options );

			// @todo Implement return handler.
			return $response->getContent();
		} catch ( TransportExceptionInterface $e ) {
			// @todo error.
		}
	}
}
