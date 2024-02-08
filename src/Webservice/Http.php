<?php

namespace SyncEngine\Webservice;

use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\ResponseInterface;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Service\Tag\TagParser;
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

	public function getAuthStepFields(): array
	{
		return [
			'' => [
				'tabs' => [
					'request'  => [
						'label'  => $this->trans( 'Request' ),
						'nested' => array_merge( [
							'url' => [
								'label'    => $this->trans( 'Url' ),
								'help'     => $this->trans( 'The URL for this authentication step' ),
								'type'     => 'text',
								'taggable' => true,
							],
						], $this->getRequestFields(), ),
					],
					'response' => [
						'label'  => $this->trans( 'Response' ),
						'nested' => [
							'format' => $this->getFormatField(),
							'tags'   => [
								'label'    => $this->trans( 'Tag storage' ),
								'help'     => $this->trans( 'Define the tags you need to store for authentication' ),
								'type'     => 'grid',
								'taggable' => true,
								'sortable' => true,
								'columns'  => [
									'type'       => [
										'label'        => $this->trans( 'Response type' ),
										'help'         => $this->trans( 'The type of response the URL will return' ),
										'customizable' => false,
										'choices'      => [
											'body'     => $this->trans( 'Body' ),
											'header'   => $this->trans( 'Header' ),
											'redirect' => $this->trans( 'Redirect URL' ),
										],
									],
									'param'      => [
										'label'       => $this->trans( 'Response param name' ),
										'help'        => $this->trans( 'The param name where the authentication parameters are located' ),
									],
									'tag'        => [
										'label'       => $this->trans( 'Tag name' ),
										'help'        => $this->trans( 'Choose the tag name in which the response param value is stored' ),
										'placeholder' => $this->trans( 'Example: token' ),
									],
									'expiration' => [
										// @todo Duration picker.
										'label'       => $this->trans( 'Expiration in hours' ),
										'help'        => $this->trans( 'Set a expiration timer for the tag value so re-authentication will be done within this expiration timeframe' ),
										'placeholder' => '00:00',
									],
								],
							],
						],
					],
					'actions'  => [
						'label'  => $this->trans( 'Actions' ),
						'nested' => [
							'success' => [
								'label'   => $this->trans( 'Success' ),
								'type'    => 'select',
								'choices' => [
									''     => $this->trans( 'Run next step (default)' ),
									'skip' => $this->trans( 'Skip next step' ),
									'stop' => $this->trans( 'Stop loop' ),
								],
							],
							'error' => [
								'label'   => $this->trans( 'Error' ),
								'type'    => 'select',
								'choices' => [
									''        => $this->trans( 'Run previous step (default)' ),
									'restart' => $this->trans( 'Restart loop from beginning' ),
									'stop'    => $this->trans( 'Stop loop' ),
								],
							],
						],
					],
				],
			],
		];
	}

	public function getAuthTags(): array
	{
		return [
			'variables' => '_input',
			'tags'      => '_input',
		];
	}

	public function authorizeStep( $authConfig, $connection ): array
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

			return [
				'success'  => true,
				'response' => $response,
				'data'     => [
					'Content' => $content,
					'Headers' => $headers,
					'Info'    => $info,
					'Options' => $clientOptions,
					'Config'  => $authConfig,
				],
			];
		} catch ( \Throwable $e ) {

			$message = [
				'Message'   => $e->getMessage(),
				'Exception' => $e::class,
			];

			$data = [
				'Message' => $message,
				'Options' => $clientOptions,
				'Config'  => $authConfig,
			];

			if ( $e instanceof ClientException ) {
				$response = $e->getResponse();
			}

			if ( isset( $response ) && $response instanceof ResponseInterface ) {
				$data['Content'] = $response->getContent( false );
				$data['Headers'] = $response->getHeaders( false );
				$data['Info']    = $response->getInfo();
			}

			return [
				'success'  => false,
				'response' => $response ?? null,
				'data'     => $data,
			];
		}
	}

	public function handleAuthorizationStepRequest( Request $request, $connection ): JsonResponse
	{
		$return = $this->authorizeStep( json_decode( $request->get( 'authConfig' ), true ), $connection );
		$status = $return['response'] instanceof ResponseInterface ? $return['response']->getStatusCode() : null;
		return new JsonResponse( $return, $status );
	}
}
