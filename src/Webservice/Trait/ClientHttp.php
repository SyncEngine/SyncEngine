<?php

namespace SyncEngine\Webservice\Trait;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use SyncEngine\Model\Trait\Format;
use SyncEngine\Service\Data\ResourceData;

trait ClientHttp
{
	use Format;

	protected HttpClientInterface $client;

	public function getClient( array $config = [] ): HttpClientInterface
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

	public function setClient( $client ): void
	{
		$this->client = $client;
	}

	/**
	 * @param  array{ query: array, headers: array, body: array, format: string|array }  $config
	 *
	 * @return array
	 */
	public function getClientOptions( array $config = [] ): array
	{
		$options = [];

		if ( ! empty( $config['query'] ) ) {
			$options['query'] = $this->parseRequestParams( $config['query'], $config['format'] ?? '' );
		}

		if ( ! empty( $config['headers'] ) ) {
			$options['headers'] = $this->parseRequestParams( $config['headers'], $config['format'] ?? '' );
		}

		if ( ! empty( $config['body'] ) ) {
			$options['body'] = $this->parseRequestParams( $config['body'], $config['format'] ?? '' );
		}

		// Auto-set content type.
		if ( ! isset( $options['headers']['Content-Type'] ) && ! empty( $config['format'] ) ) {
			$formatContentType = $this->getFormatContentType( $config['format'] );
			if ( $formatContentType ) {
				$options['headers']['Content-Type'] = $formatContentType;
			}
		}

		return $options;
	}

	public function parseRequestParams( $config, $format = '' ): mixed
	{
		if ( is_string( $config ) && ! empty( $format ) ) {
			$config = $this->decodeFormat( $format, $config );
		}

		if ( ! is_array( $config ) ) {
			return $config;
		}

		$params = new ResourceData( [] );

		foreach ( $config as $key => $value ) {
			if ( is_iterable( $value ) ) {
				$value = ResourceData::create( $value )->normalize();
			}

			if ( isset( $value['key'] ) ) {
				$key   = $value['key'];
				$value = $value['value'];
			}

			if ( isset( $params[ $key ] ) ) {
				$params[ $key ]   = (array) $params[ $key ];
				$params[ $key ][] = $value;
			} else {
				$params[ $key ] = $value;
			}
		}

		return $params->get();
	}

	public function getRequestFields( $defaults = [] ): array
	{
		return [
			'method'  => [
				'label'   => $this->trans( 'Request method' ),
				'type'    => 'select',
				'name'    => 'method',
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
			],
			'query'   => [
				'label'     => $this->trans( 'Request query' ),
				'type'      => 'params',
				'default'   => $defaults['query'] ?? null,
				'collapsed' => true,
				'taggable'  => true,
			],
			'headers' => [
				'label'     => $this->trans( 'Request header' ),
				'type'      => 'params',
				'default'   => $defaults['headers'] ?? null,
				'collapsed' => true,
				'taggable'  => true,
			],
			'body'    => [
				'label'     => $this->trans( 'Request body' ),
				'type'      => 'params',
				'manual'    => true,
				'formats'   => $this->getFormatEncodeField(),
				'default'   => $defaults['body'] ?? null,
				'collapsed' => true,
				'taggable'  => true,
			],
		];
	}
}
