<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Trait\MultistepAuth;

class Http extends NoAuth
{
	use MultistepAuth {
		getAuthFields as getAuthMultistepFields;
	}

	public function __construct()
	{
		parent::__construct();

		$this->type        = 'http';
		$this->name        = $this->trans( 'Authorization server (OAuth etc.)' );
		$this->description = $this->trans( 'Connect to a HTTP server using an authorization server.' );
	}

	public function getAuthFields(): array
	{
		return array_merge( [
			'host'          => [
				'label'    => $this->trans( 'Host' ),
				'type'     => 'text',
				'taggable' => true,
			],
		], $this->getAuthMultistepFields() );
	}

	public function getAuthTags(): array
	{
		return [
			'variables' => '_input',
			'tags'      => '_input',
		];
	}

	public function authorizeStep( $authConfig, $connection ): Result
	{
		if ( ! $connection instanceof ConnectionModel ) {
			$connection = ConnectionModel::get( $connection );
		}

		$authConfig = $this->parseAuthTags( $authConfig, $connection );

		$authConfigRequest  = $authConfig['request'] ?? [];
		$authConfigResponse = $authConfig['response'] ?? [];

		$client        = $this->getClient();
		$clientOptions = $this->getClientOptions( $authConfigRequest );

		try {

			$method   = $authConfigRequest['method'] ?? 'GET';
			$response = $client->request( $method, $authConfigRequest['url'], $clientOptions );

			// Load response content first and by doing so prevent async.
			// Headers and Info will be filled after content is fetched.
			$content = $response->getContent();
			$headers = $response->getHeaders();
			$info    = $response->getInfo();
			if ( $content && ! empty( $authConfigResponse['format'] ) ) {
				$content = $this->decodeFormat( $authConfigResponse['format'], $content, $authConfigResponse );
			}

			// Fetch param and store in connection by tag name.
			if ( ! empty( $authConfigResponse['tags'] ) ) {
				$update = false;

				$auth = $connection->getData( 'auth' );
				$auth['refs'][ $authConfig['_ref'] ] = [];

				foreach ( array_filter( $authConfigResponse['tags'] ) as $tagConfig ) {
					if ( empty( $tagConfig['tag'] ) ) {
						throw new \Exception( 'Invalid tag name' );
					}

					$result = null;
					switch ( $tagConfig['type'] ?? '' ) {
						case 'header':
							$result = $headers;
						break;
						case 'body':
							$result = $content;
						break;
						case 'redirect':
							$result   = [];
							$redirect = parse_url( (string) ( $info['url'] ?? '' ) );
							parse_str( $redirect['query'], $result );
						break;
					}

					if ( $result ) {
						$parser = new TagParser( (array) $result );

						if ( ! empty( $tagConfig['param'] ) || "0" === (string) ( $tagConfig['param'] ?? '' ) ) {
							$result = $parser->parseTag( $tagConfig['param'] );

							if ( empty( $result ) ) {
								throw new \Exception( 'Invalid or empty server response for tag: ' . $tagConfig['tag'] . ' | Param not found: ' . $tagConfig['param'] );
							}
						}

						$expiration = '';
						if ( ! empty( $tagConfig['expiration'] ) ) {
							$expiration = $tagConfig['expiration'];
							if ( is_numeric( $expiration ) ) {
								$expiration = '+ ' . $expiration . ' hours';
							} else {
								if ( $parser->hasTag( $expiration ) ) {
									$expiration = $parser->parseTagString( $expiration );
								} else {
									// Allow tags without brackets.
									$expiration = $parser->parseTag( $expiration );
								}

								$expiration = explode( ':', $expiration );
								$expiration[0] = ! empty( $expiration[0] ) ? $expiration[0] . ' hours' : '';
								$expiration[1] = ! empty( $expiration[1] ) ? $expiration[1] . ' minutes' : '';
								$expiration = '+ ' . implode( ' ', array_filter( $expiration ) );
							}

							$expiration = strtotime( $expiration );
						}

						$auth['tags'][ $tagConfig['tag'] ]    = $result;
						$auth['expires'][ $tagConfig['tag'] ] = $expiration;
						if ( ! empty( $authConfig['_ref'] ) ) {
							$auth['refs'][ $authConfig['_ref'] ][] = $tagConfig['tag'];
						}

						$connection->setData( $auth, 'auth' );
						$update = true;
					} else {
						throw new \Exception( 'Invalid or empty server response for tag: ' . $tagConfig['tag'] );
					}
				}

				if ( $update ) {
					// @todo Find another way to get the entity manager.
					$connection->update( true );
				}
			}

			$data = [
				'Content' => $content,
				'Headers' => $headers,
				'Info'    => $info,
				'Options' => $clientOptions,
				'Config'  => $authConfig,
			];

			return new Result( $data, $response );
		} catch ( \Throwable $e ) {

			$data = [
				'Options' => $clientOptions,
				'Config'  => $authConfig,
			];

			return new Result( $data, $e );
		}
	}
}
