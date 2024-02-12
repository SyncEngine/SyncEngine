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

	public function getClient( array $config ): seclibSFTP
	{
		return new seclibSFTP( $config['host'], $config['port'] );
	}

	public function getPassword( array $config ): string|PrivateKey
	{
		if ( $config['auth_method'] == "private_key" ) {
			$keyPass = ! empty( $config['keypassword'] ) ? $config['keypassword'] : null;

			return PublicKeyLoader::load( $config['key'], $keyPass );
		} else {
			return $config['password'];
		}
	}

	public function getConnection( array $config ): ?seclibSFTP
	{
		$connection  = $this->getClient( $config );
		$password    = $this->getPassword( $config );

		$login = $connection->login( $config['username'], $password );

		if ( $login ) {
			return $connection;
		}

		return null;
	}

	public function listDirectory( $connection, $config )
	{
		return $connection->nlist( $config['path'] ?? '.' );
	}

	public function putFile( $connection, $config, $local_file, $filename )
	{
		return $connection->put( $config['path'] . "/" . $filename, $local_file, FTP_BINARY );
	}

	public function remoteToLocalFile( $file, $tmpFile, $connection )
	{
		return $connection->get( $file, $tmpFile );
	}

	public function deleteSingleFile( $connection, $file )
	{
		return $connection->delete( $file );
	}

	public function deleteDirectory( $connection, $file )
	{
		return $connection->rmdir( $file );
	}

}
