<?php

namespace SyncEngine\Webservice\Trait;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Service\ResourceData;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Webservice\Helper\Result;

trait MultistepAuth
{
	public function getAuthFields(): array
	{
		return [
			'variables'     => [
				'label'       => $this->trans( 'Variables' ),
				'description' => $this->trans( 'Define static variables to be used within the authorization process.' ),
				'type'        => 'params',
			],
			'authorization' => $this->getAuthMultistepField(),
		];
	}

	public function getAuthMultistepField(): array
	{
		return [
			'label'       => $this->trans( 'Authorization steps' ),
			'description' => $this->trans( 'Define each step for the authorization process. The last step will be the actual request.' ),
			'type'        => 'repeater',
			'actions'     => [
				//'disable' => true,
				'run' => [
					'type'  => 'request',
					'props' => [
						'type'   => 'connection',
						'action' => 'authorize',
						'params' => [
							'config'     => 'element',
							'authConfig' => 'item',
							'id'         => 'entityId',
						],
					],
				],
				'disable',
				'delete',
			],
			'fieldset'    => $this->getAuthStepFields(),
		];
	}


	public function getAuthStepFields(): array
	{
		return [
			'' => [
				'tabs' => [
					'request'  => [
						'label'  => $this->trans( 'Request' ),
						'nested' => $this->getAuthStepRequestFields(),
					],
					'response' => [
						'label'  => $this->trans( 'Response' ),
						'nested' => $this->getAuthStepResponseFields(),
					],
					'actions'  => [
						'label'  => $this->trans( 'Actions' ),
						'nested' => $this->getAuthStepActionFields(),
					],
				],
			],
		];
	}

	public function getAuthStepRequestFields(): array
	{
		return array_merge( [
			'url' => [
				'label'    => $this->trans( 'Url' ),
				'help'     => $this->trans( 'The URL for this authentication step' ),
				'type'     => 'text',
				'taggable' => true,
			],
		], $this->getRequestFields(), );
	}

	public function getAuthStepResponseFields(): array
	{
		return [
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
		];
	}

	public function getAuthStepActionFields(): array
	{
		return [
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
		];
	}

	public function getAuthTags(): array
	{
		return [
			'variables' => '_input',
			'tags'      => '_input',
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

			$result = $this->authorizeStep( $authConfig, $connection );

			if ( $result->isSuccess() ) {
				$action = $authConfig['actions']['success'] ?? null;
			} else {
				$action = $authConfig['actions']['error'] ?? 'prev';

				if ( array_key_exists( $i, $errored ) ) {
					$debug   = $result->getDebugResponse();
					$message = 'Cannot authenticate on step #' . $i + 1 . ' from connection #' . $connection->getId();
					if ( ! empty( $debug['data']['Message'] ) ) {
						$debug['data']['Message']['Context'] = $message;
					} else {
						$debug['data']['Context'] = $message;
					}
					throw new \Exception( json_encode( $debug ) );
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
						$i ++; // Add extra.
					break;
					case 'stop':
					break 2;
				}
			}
		}

		unset( $config['authorization'] );

		$clientConfig = $this->parseAuthTags( $clientConfig, $connection );

		$clientConfig['host'] = $clientConfig['request']['url'] ?? $clientConfig['host'] ?? '';

		$config = new ResourceData( $config );

		// @todo Allow task config to override client config?
		return $config->replaceSafe( $clientConfig, true )->get();
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
			$refTags = $refs[ $authConfig['_ref'] ];

			foreach ( (array) $refTags as $tag ) {
				$isExpired = false;

				if ( ! empty( $tags[ $tag ] ) && ! empty( $expires[ $tag ] ) ) {
					// Tag has a value and expire value.

					if ( time() > (int) $expires[ $tag ] ) {
						// Tag expired.
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

	public function handleRequest( Request $request, $connection ): Response
	{
		$action = $request->get( 'action' );

		if ( 'authorize' === $action ) {
			return $this->authorizationStep( $request, $connection )->getDebugResponse();
		}

		return new Response( 'Invalid action' );
	}

	abstract public function authorizationStep( Request $request, $connection ): Result;
}
