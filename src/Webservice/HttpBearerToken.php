<?php

namespace SyncEngine\Webservice;

use SyncEngine\Webservice\Type\HttpWebserviceType;

class HttpBearerToken extends Http
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = HttpWebserviceType::TYPE;
		$this->icon        = 'webservice-http-bearer-token';
		$this->name        = $this->trans( 'HTTP Bearer token' );
		$this->description = $this->trans( 'Connect with Bearer Token authorization' );
	}

	public function getAuthFields(): array
	{
		return [
			'host'  => [
				'label' => $this->trans( 'Host' ),
				'type'  => 'text',
			],
			'token' => [
				'label' => $this->trans( 'Token' ),
				'type'  => 'secret',
			],
		];
	}

	public function getClientOptions( array $config = [] ): array
	{
		$options = [];

		$options['auth_bearer'] = $config['token'];

		// @todo Set content type
		// $options['headers'] => [
		//  	'Content-Type' => 'text/plain',
		// ]

		return array_merge_recursive( parent::getClientOptions( $config ), $options );
	}
}
