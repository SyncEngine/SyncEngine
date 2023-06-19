<?php

namespace App\Model\Trait;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;

trait Http
{
	protected HttpClientInterface $client;

	public function getClient( array $config = array() ): HttpClientInterface
	{
		$options = [];
		if ( $config ) {
			$options = $this->getClientOptions( $config );
		}

		if ( empty( $this->client ) ) {
			$this->setClient( HttpClient::create( $options ) );
		} else {
			$this->setClient( $this->client->withOptions( $options ) );
		}

		return $this->client;
	}

	public function getClientOptions( array $config = array() ): array
	{
		$options = [];

		if ( ! empty( $config['query'] ) ) {
			$options['query'] = [];
			foreach ( $config['query'] as $query ) {
				$options['query'][ $query['key'] ] = $query['value'];
			}
		}

		if ( ! empty( $config['headers'] ) ) {
			$options['header'] = [];
			foreach ( $config['headers'] as $header ) {
				$options['headers'][ $header['key'] ] = $header['value'];
			}
		}

		return $options;
	}

	public function getHttpFields(): array
	{
		return [
			'method' => [
				'label' => 'Request Method',
				'type' => 'select',
				'choices' => [
					'GET'     => 'GET',
					'POST'    => 'POST',
					'PUT'     => 'PUT',
					'PATCH'   => 'PATCH',
					'DELETE'  => 'DELETE',
					'HEAD'    => 'HEAD',
					'CONNECT' => 'CONNECT',
					'OPTIONS' => 'OPTION',
					'TRACE'   => 'TRACE',
				]
			],
			'query' => [
				'label' => 'Request Query',
				'type' => 'params',
			],
			'headers' => [
				'label' => 'Request Headers',
				'type' => 'params',
			],
			// @todo Should this even be available?
			/*'params' => [
				'label' => 'Request Params',
				'type' => 'params',
			],
			'body' => [
				'label' => 'Request Body',
				'type' => 'params',
			],*/
		];
	}

	public function setClient( $client ): void
	{
		$this->client = $client;
	}
}
