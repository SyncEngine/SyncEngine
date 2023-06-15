<?php

namespace App\Webservice;

class BearerToken extends NoAuth
{
	public function __construct()
	{
		parent::__construct();

		$this->type = 'bearertoken';
		$this->name = 'Bearer Token';
		$this->description = 'Connect with Bearer Token authorization';
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => 'Host',
				'type' => 'text',
			],
			'token' => [
				'label' => 'Token',
				'type' => 'text',
			],
		];
	}

	public function getClientOptions( array $config = array() ): array
	{
		$options = [];

		$options['headers'] = [ 'Authorization', 'Bearer ' . $config['token'] ];

		// @todo Set content type
		// $options['headers'] => [
		//  	'Content-Type' => 'text/plain',
		// ]

		return array_merge_recursive( parent::getClientOptions( $config ), $options );
	}
}
