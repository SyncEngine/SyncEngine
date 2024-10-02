<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Trait\MultistepAuth;
use SyncEngine\Webservice\Type\HttpWebserviceType;

class HttpMultistep extends Http
{
	use MultistepAuth {
		getAuthFields as getAuthMultistepFields;
	}

	public function __construct()
	{
		parent::__construct();

		$this->type        = HttpWebserviceType::TYPE;
		$this->name        = $this->trans( 'HTTP Authorization server' );
		$this->description = $this->trans( 'Connect to a HTTP server using an authorization server' );
	}

	public function getAuthFields(): array
	{
		return array_merge(
			[
				'host' => [
					'label'    => $this->trans( 'Host' ),
					'type'     => 'text',
					'taggable' => true,
				],
			],
			$this->getAuthMultistepFields()
		);
	}

	public function getAuthStepResponseTypeOptions(): array
	{
		return [
			'body'     => $this->trans( 'Body' ),
			'header'   => $this->trans( 'Header' ),
			'redirect' => $this->trans( 'Redirect url' ),
		];
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

			$this->parseAuthStepResponse(
				[
					'content' => $content,
					'headers' => $headers,
					'info'    => $info,
				],
				$authConfig,
				$connection
			);

			$data = [
				'Content' => $content,
				'Headers' => $headers,
				'Info'    => $info,
				'Options' => $clientOptions,
				'Config'  => $authConfig,
			];

			return new Result( true, $response, $data );
		} catch ( \Throwable $e ) {

			$data = [
				'Options' => $clientOptions,
				'Config'  => $authConfig,
			];

			return new Result( false, $e, $data );
		}
	}

	public function parseAuthStepResponseType( $response, $tagConfig )
	{
		switch ( $tagConfig['type'] ?? '' ) {
			case 'header':
				return $response['headers'];

			case 'body':
				return $response['content'];

			case 'redirect':
				$result   = [];
				$info     = $response['info'];
				$redirect = parse_url( (string) ( $info['url'] ?? '' ) );
				parse_str( $redirect['query'], $result );

				return $result;
		}

		return '';
	}
}
