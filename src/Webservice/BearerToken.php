<?php

namespace SyncEngine\Webservice;

class BearerToken extends NoAuth
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'http';
		$this->name        = $this->trans( 'bearer_token',[],"bearerToken+intl-icu");
		$this->description = $this->trans( 'description',[],"bearerToken+intl-icu");
	}

	public function getAuthFields(): array
	{
		return [
			'host'  => [
				'label' => $this->trans( 'Host',[],"bearerToken+intl-icu" ),
				'type'  => 'text',
			],
			'token' => [
				'label' => $this->trans( 'Token',[],"bearerToken+intl-icu" ),
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
