<?php

namespace App\Webservice;

use App\Component\TagParser;
use App\Controller\DefaultController;
use App\Model\ConnectionModel;
use App\Model\WebserviceModel;
use App\Service\ConnectionService;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Http extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type = 'http';
		$this->name = 'Authorization server (OAuth etc.)';
		$this->description = 'Connect to an HTTP server using an authorization server.';
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
				'type'     => 'repeater',
				'actions' => [
					//'disable' => true,
					'run' => [
						'type' => 'request',
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
					'delete'
				],
				'fieldset' => [
					'' => [
						'tabs' => [
							'request' => [
								'label' => 'Request',
								'description' => 'The description',
								'nested' => array_merge(
									[
										'url' => [
											'label' => 'Url',
											'help' => 'The URL for this authentication step',
											'type' => 'text',
										],
									],
									$this->getHttpFields(),
								)
							],
							'response' => [
								'label' => 'Response',
								'nested' => [
									'format' => $this->getFormatField(),
									'type' => [
										'label' => 'Response',
										'help' => 'The type of response the URL will return',
										'type' => 'select',
										'choices' => [
											'header'   => 'Header',
											'body'     => 'Body',
										],
									],
									'param' => [
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
									'expiration' => [
										// @todo Duration picker.
										'label' => 'Storage tag expiration in hours',
										'help' => 'Set a expiration timer for the tag value so re-authentication will done within this expiration timeframe',
										'type' => 'number',
									],
								],
							],
						],
					],
				],
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

	public function authorize( array $config ): array
	{
		$auth = $config['authorization'];
		$connection = $config['connection'];

		// The last item in the authorization list is the authorized config.
		$clientConfig = array_pop( $auth );

		foreach ( $auth as $authConfig ) {
			// Get data in each loop as it may have changed.
			$data = $connection->getData( 'auth', [] );
			$refs = $data['refs'] ?? [];
			$tags = $data['tags'] ?? [];
			$expires = $data['expires'] ?? [];
			if ( $refs && isset( $authConfig['_ref'] ) && isset( $refs[ $authConfig['_ref'] ] ) ) {
				$tag = $refs[ $authConfig['_ref'] ];

				if ( ! empty( $tags[ $tag ] ) ) {
					// Tag has a value.

					if ( empty( $expires[ $tag ] ) ) {
						// Never expires until manually removed.
						continue;
					}

					if ( time() < $expires[ $tag ] ) {
						// Not expired yet, skip authorization step.
						continue;
					}
				}
			}

			$this->authorizationRequest( $authConfig, $connection );
		}

		unset( $config['authorization'] );
		return array_merge_recursive( $config, $clientConfig );
	}

	public function authorizationRequest( $authConfig, $connection ): JsonResponse|null {
		if ( ! $connection instanceof ConnectionModel ) {
			$connection = ConnectionService::getConnection( $connection );
		}

		$authData = $connection->getData( 'auth', [] );
		$tags = $authData['tags'] ?? [];
		$authConfig = ( new TagParser( (array) $tags ) )->parseTagArray( $authConfig );

		$authConfigRequest = $authConfig['request'];
		$authConfigResponse = $authConfig['response'];

		$client = $this->getClient();
		$clientOptions = $this->getClientOptions( $authConfigRequest );

		try {

			$response = $client->request( $authConfigRequest['method'] ?? 'GET', $authConfigRequest['url'], $clientOptions );

			// Prevent async.
			$content = $response->getContent();
			if ( $content && ! empty( $authConfigResponse['format'] ) ) {
				$content = $this->fromFormat( $authConfigResponse['format'], $content, $authConfigResponse );
			}
			$headers = $response->getHeaders();

			$result = null;
			switch ( $authConfigResponse['type'] ?? '' ) {
				case 'header':
					$result = $headers;
				break;
				case 'body':
					$result = $content;
				break;
			}

			// Fetch param and store in connection by tag name.
			if ( $result && ! empty( $authConfigResponse['tag'] ) ) {
				$parser = new TagParser( (array) $result );

				if ( ! empty( $authConfigResponse['param'] ) ) {
					$result = $parser->parseTag( $authConfigResponse['param'] );
				}

				$expiration = '';
				if ( ! empty( $authConfigResponse['expiration'] ) ) {
					$expiration = $parser->parseTag( $authConfigResponse['expiration'] );
					if ( is_numeric( $expiration ) ) {
						$expiration = '+' . $expiration . ' hours';
					}
					$expiration = strtotime( $expiration );
				}

				$auth = $connection->getData( 'auth' );

				$auth['tags'][ $authConfigResponse['tag'] ] = $result;
				$auth['expires'][ $authConfigResponse['tag'] ] = $expiration;
				if ( ! empty( $authConfig['_ref'] ) ) {
					$auth['refs'][ $authConfig['_ref'] ] = $authConfigResponse['tag'];
				}

				$connection->setData( $auth, 'auth' );
				// @todo Find another way to get the entity manager.
				$connection->update( DefaultController::getEntityManager(), true );
			}

			return new JsonResponse(
				[
					'success' => true,
					'data' => [
						'Content' => $content,
						'Header'  => $headers,
						'Info'    => $response->getInfo(),
						'Options' => $clientOptions,
						'Config'  => $authConfig,
					],
				],
				$response->getStatusCode()
			);

		} catch ( \Throwable $e ) {

			if ( $e instanceof ClientException ) {
				$response = $e->getResponse();

				return new JsonResponse( [
					'success' => false,
					'data' => [
						'Message' => $e->getMessage(),
						'Content' => $response->getContent( false ),
						'Headers' => $response->getHeaders( false ),
						'Info'    => $response->getInfo(),
						'Options' => $clientOptions,
						'Config'  => $authConfig,
					]
				] );
			}

			return new JsonResponse( [
				'success' => false,
				'data' => [
					'Message' => $e->getMessage(),
					'Options' => $clientOptions,
					'Config'  => $authConfig,
				]
			] );
		}
	}

	public function handleRequest( Request $request, $connection ): Response
	{
		$action = $request->get( 'action' );

		if ( 'authorize' === $action ) {
			return $this->authorizationRequest( json_decode( $request->get('authConfig'), true ), $connection );
		}

		return new Response( 'Invalid action' );
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

			return $this->fromFormat( $config['format'] ?? '', $content );
		} catch ( \Throwable $e ) {
			// @todo error.
		}
	}

	public function send( array $config, $data )
	{
		try {
			$data = $this->toFormat( $config['format'] ?? '', $data );

			$options = $this->getClientOptions( $config );
			$options['body'] = $data;

			$client = $this->getClient();
			$response = $client->request( $config['method'] ?? 'POST', $this->getRequestUrl( $config ), $options );

			// @todo Implement return handler.
			return $response->getContent();
		} catch ( \Throwable $e ) {
			// @todo error.
		}
	}
}
