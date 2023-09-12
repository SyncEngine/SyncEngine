<?php

namespace App\Webservice;

use App\Controller\DefaultController;
use App\Model\ConnectionModel;
use App\Service\TagParser;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Http extends NoAuth
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'http';
		$this->name        = 'Authorization server (OAuth etc.)';
		$this->description = 'Connect to a HTTP server using an authorization server.';
	}

	public function getAuthFields(): array
	{
		return [
			'host'          => [
				'label'    => 'Host',
				'type'     => 'text',
				'taggable' => true,
			],
			'variables'     => [
				'label'       => 'Variables',
				'description' => 'Define static variables to be used within the authorization process.',
				'type'        => 'params',
			],
			'authorization' => [
				'label'    => 'Authorization steps',
				'type'     => 'repeater',
				'actions'  => [
					//'disable' => true,
					'run' => [
						'type'  => 'request',
						'props' => [
							'type'   => 'connection',
							'action' => 'authorize',
							'params' => [
								'element'  => 'config',
								'item'     => 'authConfig',
								'entityId' => 'id',
							],
						],
					],
					'disable',
					'delete',
				],
				'fieldset' => [
					'' => [
						'tabs' => [
							'request'  => [
								'label'  => 'Request',
								'nested' => array_merge( [
									'url' => [
										'label'    => 'Url',
										'help'     => 'The URL for this authentication step',
										'type'     => 'text',
										'taggable' => true,
									],
								], $this->getRequestFields(), ),
							],
							'response' => [
								'label'  => 'Response',
								'nested' => [
									'format' => $this->getFormatField(),
									'tags'   => [
										'label'    => 'Tag storage',
										'help'     => 'Define the tags you need to store for authentication',
										'type'     => 'columns',
										'taggable' => true,
										'columns'  => [
											'type'       => [
												'label'   => 'Response type',
												'help'    => 'The type of response the URL will return',
												'type'    => 'select',
												'choices' => [
													'body'     => 'Body',
													'header'   => 'Header',
													'redirect' => 'Redirect URL',
												],
											],
											'param'      => [
												'label'       => 'Response param name',
												'help'        => 'The param name where the authentication parameters are located',
												'type'        => 'text',
											],
											'tag'        => [
												'label'       => 'Tag name',
												'help'        => 'Choose the tag name in which the response param value is stored',
												'type'        => 'text',
												'placeholder' => 'Example: token',
											],
											'expiration' => [
												// @todo Duration picker.
												'label'       => 'Expiration in hours',
												'help'        => 'Set a expiration timer for the tag value so re-authentication will be done within this expiration timeframe',
												'type'        => 'text',
												'placeholder' => '00:00',
											],
										],
									],
								],
							],
							'actions'  => [
								'label'  => 'Actions',
								'nested' => [
									'success' => [
										'label'   => 'Success',
										'type'    => 'select',
										'choices' => [
											''     => 'Run next step (default)',
											'skip' => 'Skip next step',
											'stop' => 'Stop loop',
										],
									],
									'error' => [
										'label'   => 'Error',
										'type'    => 'select',
										'choices' => [
											''        => 'Run previous step (default)',
											'restart' => 'Restart loop from beginning',
											'stop'    => 'Stop loop',
										],
									],
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
			'variables' => 'VAR',
			'tags'      => 'TAG',
		];
	}

	public function getAuthTagsResource( array $config ): array
	{
		$resource = parent::getAuthTagsResource( $config );

		$connection = $config['connection'] ?? $config['id'] ?? 0;

		if ( ! $connection instanceof ConnectionModel ) {
			$connection = ConnectionModel::get( $connection );
		}

		$authData   = $connection->getData( 'auth', [] );
		$webservice = $connection->getConfig( 'webservice', [] );

		$resource['tags']      = $authData['tags'] ?? [];
		$resource['variables'] = $webservice['variables'] ?? [];

		return $resource;
	}

	public function getClientOptions( array $config = [] ): array
	{
		$options = [];

		// @todo Set content type
		// $options['headers'] => [
		//  	'Content-Type' => 'text/plain',
		// ]

		return array_merge_recursive( parent::getClientOptions( $config ), $options );
	}

	/**
	 * @throws \Exception
	 */
	public function authorize( array $config ): array
	{
		$auth       = $config['authorization'];
		$connection = $config['connection'] ?? $config['id'] ?? 0;
		$errored    = [];

		if ( ! $connection instanceof ConnectionModel ) {
			$connection = ConnectionModel::get( $connection );
		}

		// The last item in the authorization list is the authorized config.
		$clientConfig = array_pop( $auth );
		$checkExpired = true;

		for ( $i = 0; $i < count( $auth ); $i++ ) {
			$authConfig = $auth[ $i ];

			if ( $checkExpired && ! $this->isAuthExpired( $authConfig, $connection ) ) {
				continue;
			}

			$result = $this->authorizationRequest( $authConfig, $connection );

			if ( $result['success'] ) {
				$action = $authConfig['actions']['success'] ?? null;
			} else {
				$action = $authConfig['actions']['error'] ?? 'prev';

				if ( array_key_exists( $i, $errored ) ) {
					$message = 'Cannot authenticate on step #' . $i+1 . ' from connection #' . $connection->getId();
					if ( ! empty( $result['data']['Message']['Message'] ) ) {
						throw new \Exception( $message . ' | Error: ' . $result['data']['Message']['Message'] );
					}
					throw new \Exception( $message );
				}
				$errored[ $i ] = $authConfig;

				// Since it encountered an error, previous tags are considered invalid.
				$checkExpired = false;
			}

			if ( $action ) {
				switch ( $action ) {
					case 'prev':
						$i = $i - 2; // Remove 2 since the loop adds one on each iteration.
						break;
					case 'skip':
						$i++; // Add extra.
						break;
					case 'stop':
						break 2;
				}
			}
		}

		unset( $config['authorization'] );

		$clientConfig = $this->parseAuthTags( $clientConfig, $connection );

		$clientConfig['host'] = $clientConfig['request']['url'] ?? $clientConfig['host'] ?? '';

		return array_replace_recursive( $config, $clientConfig );
	}

	public function isAuthExpired( $authConfig, $connection ): bool
	{
		$isExpired = true;

		if ( ! $connection instanceof ConnectionModel ) {
			$connection = ConnectionModel::get( $connection );
		}

		// Get data in each loop as it may have changed.
		$data    = $connection->getData( 'auth', [] );
		$refs    = $data['refs'] ?? [];
		$tags    = $data['tags'] ?? [];
		$expires = $data['expires'] ?? [];
		if ( $refs && isset( $authConfig['_ref'] ) && isset( $refs[ $authConfig['_ref'] ] ) ) {
			$tags = $refs[ $authConfig['_ref'] ];

			foreach ( (array) $tags as $tag ) {
				$isExpired = false;

				if ( ! empty( $tags[ $tag ] ) ) {
					// Tag has a value.

					if ( empty( $expires[ $tag ] ) ) {
						// Never expires until manually removed.
						$isExpired = true;
					}

					if ( time() < $expires[ $tag ] ) {
						// Not expired yet, skip authorization step.
						$isExpired = true;
					}
				}

				if ( $isExpired ) {
					break;
				}
			}
		}

		return $isExpired;
	}

	public function parseAuthTags( $authConfig, $connection ): array
	{
		return ( new TagParser( $this->getAuthTagsResource( [ 'connection' => $connection ] ) ) )->parseTagArray( $authConfig );
	}

	public function authorizationRequest( $authConfig, $connection ): array
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
				$content = $this->fromFormat( $authConfigResponse['format'], $content, $authConfigResponse );
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

						if ( ! empty( $tagConfig['param'] ) ) {
							$result = $parser->parseTag( $tagConfig['param'] );

							if ( empty( $result ) ) {
								throw new \Exception( 'Invalid or empty server response for tag: ' . $tagConfig['tag'] . ' | Param not found: ' . $tagConfig['param'] );
							}
						}

						$expiration = '';
						if ( ! empty( $tagConfig['expiration'] ) ) {
							$expiration = $parser->parseTag( $tagConfig['expiration'] );
							if ( is_numeric( $expiration ) ) {
								$expiration = '+ ' . $expiration . ' hours';
							} else {
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
					$connection->update( DefaultController::getEntityManager(), true );
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

	public function handleRequest( Request $request, $connection ): Response
	{
		$action = $request->get( 'action' );

		if ( 'authorize' === $action ) {
			$return = $this->authorizationRequest( json_decode( $request->get( 'authConfig' ), true ), $connection );
			$status = $return['response'] instanceof ResponseInterface ? $return['response']->getStatusCode() : null;
			return new JsonResponse( $return, $status );
		}

		return new Response( 'Invalid action' );
	}
}
