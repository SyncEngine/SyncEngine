<?php

namespace SyncEngine\Webservice;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Net\SFTP as seclibSFTP;

class Sftp extends Ftp
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
			'host'         => [
				'label' => $this->trans( 'Host' ),
				'type'  => 'text',
			],
			'port'         => [
				'label'   => $this->trans( 'Port' ),
				'type'    => 'number',
				'default' => 22,
			],
			'auth_method'  => [
				'label'   => $this->trans( 'Authentication type' ),
				'type'    => 'select',
				'choices' => [
					'private_key'       => $this->trans( 'Private key' ),
					'username_password' => $this->trans( 'Username - Password' ),
				],
			],
			'key'          => [
				'label'      => $this->trans( 'Private key' ),
				'type'       => 'secret',
				'conditions' => [
					'auth_method' => 'private_key',
				],
			],
			'key_password' => [
				'label'      => $this->trans( 'Private key password' ),
				'type'       => 'secret',
				'help'       => $this->trans( "If your private key is password protected, you can fill in that password here" ),
				'conditions' => [
					'auth_method' => 'private_key',
				],
			],
			'username'     => [
				'label' => $this->trans( 'Username' ),
				'type'  => 'secret',
			],
			'password'     => [
				'label'      => $this->trans( 'Password' ),
				'type'       => 'secret',
				'conditions' => [
					'auth_method' => 'username_password',
				],
			],
		];
	}

	public function getPassword( array $config ): string|PrivateKey
	{
		if ( 'private_key' === $config['auth_method'] ) {
			$keyPass = ! empty( $config['key_password'] ) ? $config['key_password'] : null;

			return PublicKeyLoader::load( $config['key'], $keyPass );
		} else {
			return $config['password'];
		}
	}

	public function getClient( array $config ): ?seclibSFTP
	{
		$client   = new seclibSFTP( $config['host'], $config['port'] );;
		$password = $this->getPassword( $config );

		$login = $client->login( $config['username'], $password );

		if ( $login ) {
			return $client;
		}

		return null;
	}

	public function listDirectory( $client, $config )
	{
		return $client->nlist( $config['path'] ?? '.' );
	}

	public function putFile( $client, $config, $local_file, $filename )
	{
		return $client->put( $config['path'] . "/" . $filename, $local_file, FTP_BINARY );
	}

	public function createDirectory( $config, $data )
	{
		$client = $this->getClient( $config );

		return $client->mkdir( $config["filename"] );
	}

	public function fetchFile( $file, $tmpFile, $client )
	{
		return $client->get( $file, $tmpFile );
	}

	public function deleteSingleFile( $client, $file )
	{
		return $client->delete( $file );
	}

	public function deleteDirectory( $config, $data )
	{
		$client = $this->getClient( $config );

		return $client->rmdir( $config["filename"] );
	}

}
