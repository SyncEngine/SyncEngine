<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use phpseclib3\Crypt\PublicKeyLoader;
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
			'host'        => [
				'label' => 'Host',
				'type'  => 'text',
			],
			'username'    => [
				'label' => 'Username',
				'type'  => 'text',
			],
			'auth_method' => [
				'label'   => 'Authentication type',
				'type'    => 'select',
				'choices' => [
					'private_key'       => 'Private key',
					'username_password' => 'Username - Password',
				],
			],
			'key'         => [
				'label'        => 'Private key',
				'type'         => 'text',
				'conditionals' => [
					'auth_method' => 'private_key',
				],
			],
			'keypassword' => [
				'label'        => 'Private key password',
				'type'         => 'password',
				'help'         => "If your private key is password protected, you can fill in that password here",
				'conditionals' => [
					'auth_method' => 'private_key',
				],
			],
			'password'    => [
				'label'        => 'Password',
				'type'         => 'password',
				'conditionals' => [
					'auth_method' => 'username_password',
				],
			],
			'port'        => [
				'label'   => 'Port',
				'type'    => 'number',
				'default' => 22,
			],
		];
	}

	public function getFields( $defaults = [] ): array
	{
		return $this->getRequestFields( $defaults );
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
			var_dump( $content );
			die;
		} else {
			throw new \Exception( 'Could not authenticate to the SFTP server' );
		}

		return $this->fromFormat( $config['format'] ?? '', $content );
	}

	public function getClient( array $config ): seclibSFTP
	{
		return new seclibSFTP( $config['host'] );
	}

	public function getPass(array $config): string|PublicKeyLoader
	{
		if($config['auth_method'] == "private_key"){
			$keyPass = !empty($config['keypassword']) ? $config['keypassword'] : null;
			return PublicKeyLoader::load(strval($config['key']),$keyPass);
			//return PublicKeyLoader::loadPrivateKey( $config['key'], $keyPass);
		}else{
			return $config['password'];
		}
	}

	public function getClientLoggedIn( array $config ): ?seclibSFTP
	{
		$sftp = $this->getClient( $config );
		$pw = $this->getPass($config);
		$login = $sftp->login( $config['username'], $pw );

		if ( $login ) {
			return $sftp;
		}

		return null;
	}
}
