<?php

namespace App\Webservice;

class Basic extends NoAuth
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'http';
		$this->name        = 'Basic';
		$this->description = 'Connect with basic authorization';
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => 'Host',
				'type'  => 'text',
			],
			'username' => [
				'label' => 'Username / Key',
				'type'  => 'text',
			],
			'password' => [
				'label' => 'Password / Secret',
				'type'  => 'password',
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
