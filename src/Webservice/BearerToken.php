<?php

namespace SyncEngine\Webservice;

class BearerToken extends NoAuth
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'http';
		$this->name        = $this->trans( 'Bearer Token' );
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
