<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Ftp extends WebserviceModel
{
	public function __construct()
	{
		$this->type = 'ftp';
		$this->name = 'FTP';
		$this->description = 'Connect to an FTP server to upload and/or download files';

		parent::__construct();
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => 'Host',
				'type' => 'text',
			],
			'username' => [
				'label' => 'Username',
				'type' => 'text',
			],
			'password' => [
				'label' => 'Password',
				'type' => 'password',
			],
			'port' => [
				'label' => 'Port',
				'type' => 'text',
			],
		];
	}

	public function getFields(): array
	{
		// @todo define task fields.
		// @todo allow override or even custom auth fields.
		return [];
	}

	public function getRequestUrl( array $config ): string {
		// TODO: Implement getRequestUrl() method.
	}

	public function retrieve( array $config )
	{
		// TODO: Implement retrieve() method.
	}

	public function send( array $config, $data )
	{
		// TODO: Implement send() method.
	}
}
