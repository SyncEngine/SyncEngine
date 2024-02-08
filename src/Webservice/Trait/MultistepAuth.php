<?php

namespace SyncEngine\Webservice\Trait;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Service\ResourceData;
use SyncEngine\Service\Tag\TagParser;

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

	abstract public function getAuthStepFields(): array;

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

			if ( $result['success'] ) {
				$action = $authConfig['actions']['success'] ?? null;
			} else {
				$action = $authConfig['actions']['error'] ?? 'prev';

				if ( array_key_exists( $i, $errored ) ) {
					$message = 'Cannot authenticate on step #' . $i+1 . ' from connection #' . $connection->getId();
					if ( ! empty( $result['data']['Message'] ) ) {
						$result['data']['Message']['Context'] = $message;
					} else {
						$result['data']['Context'] = $message;
					}
					throw new \Exception( json_encode( $result ) );
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
			return $this->handleAuthorizationStepRequest( $request, $connection );
		}

		return new Response( 'Invalid action' );
	}

	abstract public function handleAuthorizationStepRequest( Request $request, $connection ): JsonResponse;
}
