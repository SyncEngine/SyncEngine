<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP as seclibSFTP;

class Sftp extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'ftp';
		$this->name        = $this->trans( 'SFTP' );
		$this->description = $this->trans( 'Connect to an SFTP server to upload and/or download files' );
	}

	public function getAuthFields(): array
	{
		return [
			'host'        => [
				'label' => $this->trans( 'Host' ),
				'type'  => 'text',
			],
			'username'    => [
				'label' => $this->trans( 'Username' ),
				'type'  => 'secret',
			],
			'auth_method' => [
				'label'   => $this->trans( 'Authentication type' ),
				'type'    => 'select',
				'choices' => [
					'private_key'       => $this->trans( 'Private key' ),
					'username_password' => $this->trans( 'Username - Password' ),
				],
			],
			'key'         => [
				'label'      => $this->trans( 'Private key' ),
				'type'       => 'secret',
				'conditions' => [
					'auth_method' => 'private_key',
				],
			],
			'keypassword' => [
				'label'      => $this->trans( 'Private key password' ),
				'type'       => 'secret',
				'help'       => $this->trans( "If your private key is password protected, you can fill in that password here" ),
				'conditions' => [
					'auth_method' => 'private_key',
				],
			],
			'password'    => [
				'label'      => $this->trans( 'Password' ),
				'type'       => 'secret',
				'conditions' => [
					'auth_method' => 'username_password',
				],
			],
			'port'        => [
				'label'   => $this->trans( 'Port' ),
				'type'    => 'number',
				'default' => 22,
			],
		];
	}

	public function getFields( array $defaults = [] ): array
	{
		return $this->getRequestFields( $defaults );
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	public function getRequestFields( array $defaults = [] ): array
	{
		return [
			'filename' => [
				'label' => $this->trans( 'Filename' ),
				'type'  => 'text',
			],
			'path'     => [
				'label' => $this->trans( 'Path' ),
				'type'  => 'text',
			],
			'override' => [
				'label'     => $this->trans( 'Overwrite if file exists' ),
				'type'      => 'boolean',
				'condition' => [], //@ToDo task is sender
			],
			'format'   => $this->getFormatField(),
		];
	}

	public function send( array $config, $data ): Result
	{
		return new Result( $data );
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$authenticated = $this->getClientLoggedIn( $config );

		if ( $authenticated ) {
			$path = $config['path'] ?? '.';
			$content = $authenticated->get( $path . "/" . $config['filename'] );
		} else {
			throw new \Exception( 'Could not authenticate to the SFTP server' );
		}

		return new Result( $this->decodeFormat( $config['format'] ?? '', $content ) );
	}

	public function getClient( array $config ): seclibSFTP
	{
		return new seclibSFTP( $config['host'], $config['port']);
	}

	public function getPass( array $config ): string|PrivateKey
	{
		if ( $config['auth_method'] == "private_key" ) {
			$keyPass = ! empty( $config['keypassword'] ) ? $config['keypassword'] : null;

			return PublicKeyLoader::load( $config['key'] , $keyPass);
		} else {
			return $config['password'];
		}
	}

	public function getClientLoggedIn( array $config ): ?seclibSFTP
	{
		$sftp  = $this->getClient( $config );
		$pw    = $this->getPass( $config );
		$login = $sftp->login( $config['username'], $pw );

		if ( $login ) {
			return $sftp;
		}

		return null;
	}
}
