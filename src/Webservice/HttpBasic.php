<?php

namespace SyncEngine\Webservice;

use SyncEngine\Webservice\Type\HttpWebserviceType;

class HttpBasic extends Http
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = HttpWebserviceType::TYPE;
		$this->name        = $this->trans( 'HTTP Basic Auth' );
		$this->description = $this->trans( 'Connect with basic authorization' );
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => $this->trans( 'Host' ),
				'type'  => 'text',
			],
			'username' => [
				'label' => $this->trans( 'Username / key' ),
				'type'  => 'secret',
			],
			'password' => [
				'label' => $this->trans( 'Password / Secret' ),
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
