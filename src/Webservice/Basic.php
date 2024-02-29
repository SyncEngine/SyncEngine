<?php

namespace SyncEngine\Webservice;

class Basic extends NoAuth
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'http';
		$this->name        = $this->trans( 'Basic',[],"webservice/basic");
		$this->description = $this->trans( 'Connect with basic authorization',[],"webservice/basic");
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => $this->trans( 'Host',[],"webservice/basic" ),
				'type'  => 'text',
			],
			'username' => [
				'label' => $this->trans( 'Username / key',[],"webservice/basic" ),
				'type'  => 'secret',
			],
			'password' => [
				'label' => $this->trans( 'Password / Secret',[],"webservice/basic" ),
				'type'  => 'secret',
			],
		];
	}

	public function getClientOptions( array $config = [] ): array
	{
		$options = [];

		if ( empty( $config['password'] ) ) {
			$options['auth_basic'] = [ $config['username'], '' ];
		} else {
			$options['auth_basic'] = [ $config['username'], $config['password'] ];
		}

		// @todo Set content type
		// $options['headers'] => [
		//  	'Content-Type' => 'text/plain',
		// ]

		return array_merge_recursive( parent::getClientOptions( $config ), $options );
	}
}
