<?php

namespace App\Webservice;

use App\Model\WebserviceModel;

class Ftp extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type = 'ftp';
		$this->name = 'FTP';
		$this->description = 'Connect to an FTP server to upload and/or download files';
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
		return [
			'filename' => [
				'label' => 'Filename',
				'type' => 'text',
			],
			'path' => [
				'label' => 'Path',
				'type' => 'text',
			],
		];
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
