<?php

namespace SyncEngine\Webservice;

class Basic extends NoAuth
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'http';
		$this->name        = $this->trans( 'basic',[],"basic+intl-icu");
		$this->description = $this->trans( 'description',[],"basic+intl-icu");
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => $this->trans( 'Host',[],"basic+intl-icu" ),
				'type'  => 'text',
			],
			'username' => [
				'label' => $this->trans( 'Username_Key',[],"basic+intl-icu" ),
				'type'  => 'secret',
			],
			'password' => [
				'label' => $this->trans( 'Password_Secret',[],"basic+intl-icu" ),
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
