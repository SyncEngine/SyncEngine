<?php

namespace App\Webservice;

use App\Component\TagParser;
use App\Controller\DefaultController;
use App\Model\ConnectionModel;
use App\Model\WebserviceModel;
use App\Service\ConnectionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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
				'type'     => 'repeater',
				'actions' => [
					//'disable' => true,
					'run' => [
						'type' => 'RequestModal',
						'props' => [
							'type'   => 'connection',
							'action' => 'authorize',
							'params' => [
								'element' => 'config',
								'item'    => 'authConfig',
							],
						],
					]
				],
				'fieldset' => [
					'rr' => [
						'tabs' => [
							'request' => [
								'label' => 'Request',
								'description' => 'The description',
								'fields' => array_merge(
									[
										'url' => [
											'label' => 'Url',
											'help' => 'The URL for this authentication step',
											'type' => 'text',
										],
									],
									parent::getHttpFields(),
									parent::getFormatFields()
								)
							],
							'response' => [
								'label' => 'Response',
								'fields' => [
									'response' => [
										'label' => 'Response',
										'help' => 'The type of response the URL will return',
										'type' => 'select',
										'choices' => [
											'header'   => 'Header',
											'body'     => 'Body',
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
			$this->authorizationRequest( $authConfig, $connection );
		}

		unset( $config['authorization'] );
		return array_merge_recursive( $config, $clientConfig );
	}

	public function authorizationRequest( $config, $connection ): JsonResponse|null {
		try {
			$client = $this->getClient();
			$response = $client->request( $config['method'] ?? 'GET', $config['url'], $this->getClientOptions( $config ) );

			// Prevent async.
			$content = $response->getContent();
			if ( ! empty( $config['format'] ) ) {
				$content = $this->fromFormat( $config['format'], $content );
			}
			$headers = $response->getHeaders();

			$result = null;
			switch ( $config['response'] ?? '' ) {
				case 'header':
					$result = $headers;
				break;
				case 'body':
					$result = $content;
				break;
			}

			// Fetch param and store in connection by tag name.
			if ( $result && ! empty( $config['tag'] ) && $connection ) {
				if ( ! $connection instanceof ConnectionModel ) {
					$connection = ConnectionService::getConnection( $connection );
				}

				if ( ! empty( $config['response_param'] ) ) {
					$result = ( new TagParser( $result ) )->parseTag( $config['response_param'] );
				}

				$auth = [
					'result' => $result,
					'expires' => $config['tag_expiration'] ?? '',
				];

				$connection->setData( $auth, $config['tag'] );
				$connection->update( DefaultController::getEntityManager(), true );
			}

			return new JsonResponse( [ 'content' => $content, 'header' => $headers ], $response->getStatusCode() );
		} catch ( TransportExceptionInterface $e ) {
			// @todo error.
			return new JsonResponse( $e->getMessage() );
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
