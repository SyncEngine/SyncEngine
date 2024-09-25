<?php

namespace SyncEngine\Webservice\Trait;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\Format\DurationFormatter;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Webservice\Exception\AuthResultException;
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
			'authorize'     => [
				'label'       => $this->trans( 'Authorized request base config' ),
				'description' => $this->trans(
					'Define the base request configuration. This will be used to set the default params for any request using this connection.'
				),
				'nested'      => array_merge(
					[
						'host' => [
							'label'    => $this->trans( 'Override host' ),
							'type'     => 'text',
							'taggable' => true,
						],
					],
					$this->getFields()
				),
			],
		];
	}

	public function getAuthMultistepField(): array
	{
		return [
			'label'       => $this->trans( 'Authorization process steps' ),
			'description' => $this->trans(
				'Define each step for the authorization process.'
			),
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
		return array_merge(
			[
				'url' => [
					'label'    => $this->trans( 'Url' ),
					'help'     => $this->trans( 'The URL for this authentication step' ),
					'type'     => 'text',
					'taggable' => true,
				],
			],
			$this->getRequestFields(),
		);
	}

	public function getAuthStepResponseFields(): array
	{
		$typeOptions = $this->getAuthStepResponseTypeOptions();

		return [
			'format' => $this->getFormatField(),
			'tags'   => [
				'label'    => $this->trans( 'Tag storage' ),
				'help'     => $this->trans( 'Define the tags you need to store for authentication' ),
				'type'     => 'grid',
				'taggable' => true,
				'sortable' => true,
				'columns'  => [
					'type'       => $typeOptions ? [
						'label'        => $this->trans( 'Response type' ),
						'help'         => $this->trans(
							'The type of response the URL will return'
						),
						'customizable' => false,
						'choices'      => $typeOptions,
					] : [],
					'param'      => [
						'label' => $this->trans( 'Response param name' ),
						'help'  => $this->trans( 'The param name where the authentication parameters are located' ),
					],
					'tag'        => [
						'label'       => $this->trans( 'Tag name' ),
						'help'        => $this->trans(
							'Choose the tag name in which the response param value is stored'
						),
						'placeholder' => $this->trans( 'Example: token' ),
					],
					'expiration' => [
						// @todo Duration picker.
						'label'       => $this->trans( 'Expiration' ),
						'help'        => [
							$this->trans(
								'Set a expiration timer for the tag value so re-authentication will be done within this expiration timeframe'
							),
							$this->trans( 'Supported formats:' ),
							$this->trans( 'Timestamp or date' ),
							$this->trans( 'Time format: ({time_format})', [ 'time_format' => '`02:30:15`' ] ),
							$this->trans( 'Text format: ({time_string})', [ 'time_string' => '`"2 hours 30 minutes 15 seconds"`' ] ),
						],
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
			'error'   => [
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

	public function getAuthStepResponseTypeOptions(): array
	{
		return [];
	}

	public function getAuthTags(): array
	{
		return [
			'variables' => '_input',
			'tags'      => '_input',
		];
	}

	/**
	 * @throws AuthResultException
	 */
	public function authorize( array $config ): array
	{
		$authSteps  = $config['authorization'] ?? [];
		$connection = $config['connection'] ?? $config['id'] ?? 0;
		$errored    = [];

		if ( ! $connection instanceof ConnectionModel ) {
			$connection = ConnectionModel::get( $connection );
		}

		if ( $authSteps ) {
			$checkExpired = true;
			for ( $i = 0; $i < count( $authSteps ); ( $i < 0 ) ? $i = 0 : $i ++ ) {
				$authStepConfig = $authSteps[ $i ];

				if ( $checkExpired && ! $this->isAuthExpired( $authStepConfig, $connection ) ) {
					continue;
				}

				$authStepConfig = $this->parseAuthTags( $authStepConfig, $connection );
				$result         = $this->authorizeStep( $authStepConfig, $connection );

				if ( $result->isSuccess() ) {
					$action = $authStepConfig['actions']['success'] ?? null;
				} else {
					$action = $authStepConfig['actions']['error'] ?? 'prev';

					// If this step errors again then we can safely assume that the config is the problem.
					if ( array_key_exists( $i, $errored ) ) {
						$debug = $result->getDebugResponse();

						$message = $this->trans(
							'Cannot authenticate on step #{step} from connection #{connectionID}',
							[ 'step' => $i + 1, 'connectionID' => $connection->getId() ]
						);
						if ( ! empty( $debug['data']['Message'] ) ) {
							$debug['data']['Message']['Context'] = $message;
						} else {
							$debug['data']['Context'] = $message;
						}
						throw new AuthResultException( json_encode( $debug ) );
					}
					$errored[ $i ] = $authStepConfig;

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
		}

		$authConfig = $config['authorize'];

		// @todo Remove redundant/old config?
		//unset( $config['authorization'] );
		//unset( $config['authorize'] );

		if ( empty( $authConfig['host'] ) ) {
			// Set host in auth config to parse tags.
			$authConfig['host'] = $config['host'] ?? '';
		}

		$authConfig = $this->parseAuthTags( $authConfig, $connection );

		if ( ! empty( $authConfig['host'] ) ) {
			// Host override.
			$config['host'] = $authConfig['host'];
		}

		$authConfig = new ResourceData( $authConfig );

		return $authConfig->merge( $config, true )->get();
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
				$expiresTime = $expires[ $tag ] ?? 0;

				if ( empty( $tags[ $tag ] ) ) {
					// Tag has no value so consider it expired.
					$isExpired = true;
					break;
				}

				// Expired time is a boolean so disable any time checks and just use the value.
				if ( is_bool( $expiresTime ) ) {
					$isExpired = $expiresTime;
				} else {
					// Tag has a value and an expiration timestamp.
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
		return ( new TagParser( $this->getAuthTagsResource( [ 'connection' => $connection ] ), false ) )->parseArray(
			$authConfig
		);
	}

	abstract public function authorizeStep( $authConfig, $connection ): Result;

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

	public function parseAuthStepResponse( $response, $authConfig, $connection ): void
	{
		$authConfigResponse = $authConfig['response'] ?? [];

		// Fetch param and store in connection by tag name.
		if ( ! empty( $authConfigResponse['tags'] ) ) {
			$update = false;
			$auth   = $connection->getData( 'auth' );

			$auth['refs'][ $authConfig['_ref'] ] = [];

			foreach ( array_filter( $authConfigResponse['tags'] ) as $tagConfig ) {
				if ( empty( $tagConfig['tag'] ) ) {
					throw new InvalidConfigException( $this->trans( 'Invalid tag name' ) );
				}

				$result = $this->parseAuthStepResponseType( $response, $tagConfig );

				if ( $result ) {
					$parser = new TagParser( is_scalar( $result ) ? (array) $result : $result );

					if ( ! empty( $tagConfig['param'] ) || "0" === (string) ( $tagConfig['param'] ?? '' ) ) {
						$result = $parser->parseTag( $tagConfig['param'] );

						if ( empty( $result ) ) {

							throw new AuthResultException(
								$this->trans(
									'Invalid or empty server response for tag:{tag} | Param not found:{param}',
									[ 'tag' => $tagConfig['tag'], 'param' => $tagConfig['param'] ]
								)
							);
						}
					}

					$expiration = '';
					if ( ! empty( $tagConfig['expiration'] ) ) {
						$expiration = $tagConfig['expiration'];

						if ( ! is_numeric( str_replace( ':', '', $expiration ) ) ) {
							if ( $parser->hasTag( $expiration ) ) {
								$expiration = $parser->parseString( $expiration );
							} else {
								// Allow tags without brackets.
								$expiration = $parser->parseTag( $expiration );
							}
						}

						$expiration = $this->parseTimeString( $expiration );
					}

					$auth['tags'][ $tagConfig['tag'] ]    = $result;
					$auth['expires'][ $tagConfig['tag'] ] = $expiration;
					if ( ! empty( $authConfig['_ref'] ) ) {
						$auth['refs'][ $authConfig['_ref'] ][] = $tagConfig['tag'];
					}

					$connection->setData( $auth, 'auth' );
					$update = true;
				} else {
					throw new AuthResultException(
						$this->trans(
							'Invalid or empty server response for tag:{tag}',
							[ 'tag' => $tagConfig['tag'] ]
						)
					);
				}
			}

			if ( $update ) {
				// @todo Find another way to get the entity manager.
				$connection->update( true );
			}
		}
	}

	public function parseAuthStepResponseType( $response )
	{
		return $response;
	}

	public function parseTimeString( $string ): int
	{
		if ( is_numeric( $string ) && $string > 1000000000 ) {
			// Timestamp.
			return (int) $string;
		}

		$format = new DurationFormatter();

		return $format->toTimestamp( $string );
	}

	public function handleRequest( Request $request, $connection ): Response
	{
		$action = $request->get( 'action' );

		if ( 'authorize' === $action ) {
			$authConfig = json_decode( $request->get( 'authConfig' ), true );
			$authConfig = $this->parseAuthTags( $authConfig, $connection );

			return $this->authorizeStep( $authConfig, $connection )->getDebugResponse();
		}

		return parent::handleRequest( $request, $connection );
	}
}
