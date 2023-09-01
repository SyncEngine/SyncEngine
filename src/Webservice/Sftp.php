<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use phpseclib3\Net\SFTP as seclibSFTP;

class Sftp extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'ftp';
		$this->name        = 'SFTP';
		$this->description = 'Connect to an SFTP server to upload and/or download files';
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => 'Host',
				'type'  => 'text',
			],
			'username' => [
				'label' => 'Username',
				'type'  => 'text',
			],
			'password' => [
				'label' => 'Password / key',
				'type'  => 'password',
			],
			'port'     => [
				'label' => 'Port',
				'type'  => 'number',
				'default' => 22,
			],
		];
	}

	public function getFields(): array
	{
		return $this->getRequestFields();
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	public function getRequestFields( $defaults = [] ): array
	{
		return [
			'filename' => [
				'label' => 'Filename',
				'type'  => 'text',
			],
			'path'     => [
				'label' => 'Path',
				'type'  => 'text',
			],
			'override' => [
				'label'       => 'Overwrite if file exists',
				'type'        => 'boolean',
				'conditional' => [], //@ToDo task is sender
			],
			'format'   => $this->getFormatField(),
		];
	}

	public function send( array $config, $data )
	{
		return $data;
	}

	public function retrieve( array $config )
	{
		$authenticated = $this->getClientLoggedIn( $config );
		if ( $authenticated ) {
			$content = $authenticated->get( $config['path'] . "/" . $config['filename'] );
		} else {
			echo( 'Could not authenticate to the SFTP server' );
		}

		return $this->fromFormat( $config['format'] ?? '', $content );
	}

	public function getClient( array $config ): seclibSFTP
	{
		return new seclibSFTP( $config['host'] );
	}

	public function getClientLoggedIn( array $config ): ?seclibSFTP
	{
		$sftp  = $this->getClient( $config );
		$login = $sftp->login( $config['username'], $config['password'] );

		if ( $login ) {
			return $sftp;
		}

		return null;
	}
}
