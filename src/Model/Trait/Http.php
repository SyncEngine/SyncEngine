<?php

namespace App\Model\Trait;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;

trait Http
{
	use Format;

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
			foreach ( $config['query'] as $key => $value ) {
				if ( is_string( $key ) ) {
					$options['query'][ $key ] = $value;
				} else {
					$options['query'][ $value['key'] ] = $value['value'];
				}
			}
		}

		if ( ! empty( $config['headers'] ) ) {
			$options['headers'] = [];
			foreach ( $config['headers'] as $key => $value ) {
				if ( is_string( $key ) ) {
					$options['headers'][ $key ] = $value;
				} else {
					$options['headers'][ $value['key'] ] = $value['value'];
				}
			}
		}

		if ( ! empty( $config['body'] ) ) {
			$options['body'] = [];
			foreach ( $config['body'] as $key => $value ) {
				if ( is_string( $key ) ) {
					$options['body'][ $key ] = $value;
				} else {
					$options['body'][ $value['key'] ] = $value['value'];
				}
			}
		}

		return $options;
	}

	public function getHttpFields( $defaults = [] ): array
	{
		return [
			'method' => [
				'label'   => 'Request Method',
				'type'    => 'select',
				'default' => $defaults['method'] ?? null,
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
				],
				'fields' => array_merge(
					[
						'query' => [
							'label' => 'Request Query',
							'type' => 'params',
							'default' => $defaults['query'] ?? null,
						],
						'headers' => [
							'label' => 'Request Headers',
							'type' => 'params',
							'default' => $defaults['headers'] ?? null,
						],
						'body' => [
							'label' => 'Request Body',
							'type' => 'params',
							'default' => $defaults['body'] ?? null,
						],
					],
					$this->getFormatFields()
				)
			],
		];
	}

	public function setClient( $client ): void
	{
		$this->client = $client;
	}
}
