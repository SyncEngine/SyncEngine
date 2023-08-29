<?php

namespace App\Webservice;

use App\Controller\DefaultController;
use App\Model\ConnectionModel;
use App\Service\ConnectionService;
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
		$this->description = 'Connect to an HTTP server using an authorization server.';
	}

	public function getAuthFields(): array
	{
		return [
			'host'          => [
				'label' => 'Host',
				'type'  => 'text',
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
										'label' => 'Url',
										'help'  => 'The URL for this authentication step',
										'type'  => 'text',
									],
								], $this->getRequestFields(), ),
							],
							'response' => [
								'label'  => 'Response',
								'nested' => [
									'format' => $this->getFormatField(),
									'tags'   => [
										'label'   => 'Tag storage',
										'help'    => 'Define the tags you need to store for authentication',
										'type'    => 'columns',
										'columns' => [
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
												'placeholder' => 'token',
											],
											'tag'        => [
												'label'       => 'Tag name',
												'help'        => 'Choose the tag name in which the response param value is stored',
												'type'        => 'text',
												'placeholder' => 'token',
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
						],
					],
				],
			],
		];
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

	public function authorize( array $config ): array
	{
		$auth       = $config['authorization'];
		$connection = $config['connection'];

		// The last item in the authorization list is the authorized config.
		$clientConfig = array_pop( $auth );

		foreach ( $auth as $authConfig ) {
			// Get data in each loop as it may have changed.
			$data    = $connection->getData( 'auth', [] );
			$refs    = $data['refs'] ?? [];
			$tags    = $data['tags'] ?? [];
			$expires = $data['expires'] ?? [];
			if ( $refs && isset( $authConfig['_ref'] ) && isset( $refs[ $authConfig['_ref'] ] ) ) {
				$tags = $refs[ $authConfig['_ref'] ];

				foreach ( (array) $tags as $tag ) {
					$skip = false;

					if ( ! empty( $tags[ $tag ] ) ) {
						// Tag has a value.

						if ( empty( $expires[ $tag ] ) ) {
							// Never expires until manually removed.
							$skip = true;
						}

						if ( time() < $expires[ $tag ] ) {
							// Not expired yet, skip authorization step.
							$skip = true;
						}
					}

					if ( $skip ) {
						continue 2;
					}
				}
			}

			$this->authorizationRequest( $authConfig, $connection );
		}

		unset( $config['authorization'] );

		return array_merge_recursive( $config, $clientConfig );
	}

	public function authorizationRequest( $authConfig, $connection ): JsonResponse|null
	{
		if ( ! $connection instanceof ConnectionModel ) {
			$connection = ConnectionModel::get( $connection );
		}

		$authData   = $connection->getData( 'auth', [] );
		$tags       = $authData['tags'] ?? [];
		$webservice = $connection->getConfig( 'webservice', [] );
		$variables  = $webservice['variables'] ?? [];
		$authConfig = ( new TagParser( [ 'variables' => $variables, 'tags' => $tags ] ) )->parseTagArray( $authConfig );

		$authConfigRequest  = $authConfig['request'] ?? [];
		$authConfigResponse = $authConfig['response'] ?? [];

		$client        = $this->getClient();
		$clientOptions = $this->getClientOptions( $authConfigRequest );

		try {

			$response = $client->request( $authConfigRequest['method']
			                              ??
			                              'GET', $authConfigRequest['url'], $clientOptions );

			// Prevent async.
			$content = $response->getContent();
			if ( $content && ! empty( $authConfigResponse['format'] ) ) {
				$content = $this->fromFormat( $authConfigResponse['format'], $content, $authConfigResponse );
			}
			$headers = $response->getHeaders();
			$info    = $response->getInfo();

			// Fetch param and store in connection by tag name.
			if ( ! empty( $authConfigResponse['tags'] ) ) {
				$update = false;

				$auth = $connection->getData( 'auth' );
				$auth['refs'][ $authConfig['_ref'] ] = [];

				foreach ( $authConfigResponse['tags'] as $tagConfig ) {

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
					}
				}

				if ( $update ) {
					// @todo Find another way to get the entity manager.
					$connection->update( DefaultController::getEntityManager(), true );
				}
			}

			return new JsonResponse( [
				'success' => true,
				'data'    => [
					'Content' => $content,
					'Headers' => $headers,
					'Info'    => $info,
					'Options' => $clientOptions,
					'Config'  => $authConfig,
				],
			], $response->getStatusCode() );
		} catch ( \Throwable $e ) {

			$message = [
				'Message'   => $e->getMessage(),
				'Exception' => $e::class,
			];

			if ( $e instanceof ClientException ) {
				$response = $e->getResponse();

				return new JsonResponse( [
					'success' => false,
					'data'    => [
						'Message' => $message,
						'Content' => $response->getContent( false ),
						'Headers' => $response->getHeaders( false ),
						'Info'    => $response->getInfo(),
						'Options' => $clientOptions,
						'Config'  => $authConfig,
					],
				] );
			}

			$data = [
				'Message' => $message,
				'Options' => $clientOptions,
				'Config'  => $authConfig,
			];
			if ( isset( $response ) && $response instanceof ResponseInterface ) {
				$data['Content'] = $response->getContent( false );
				$data['Headers'] = $response->getHeaders( false );
				$data['Info']    = $response->getInfo();
			}

			return new JsonResponse( [
				'success' => false,
				'data'    => $data,
			] );
		}
	}

	public function handleRequest( Request $request, $connection ): Response
	{
		$action = $request->get( 'action' );

		if ( 'authorize' === $action ) {
			return $this->authorizationRequest( json_decode( $request->get( 'authConfig' ), true ), $connection );
		}

		return new Response( 'Invalid action' );
	}
}
