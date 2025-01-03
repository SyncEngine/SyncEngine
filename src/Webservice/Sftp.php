<?php

namespace SyncEngine\Webservice;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Net\SFTP as seclibSFTP;
use SyncEngine\Webservice\Exception\AuthResultException;
use SyncEngine\Webservice\Exception\ResultException;
use SyncEngine\Webservice\Type\FtpWebserviceType;

class Sftp extends Ftp
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = FtpWebserviceType::TYPE;
		$this->name        = $this->trans( 'SFTP' );
		$this->description = $this->trans(
			'Connect to a SFTP server to upload and/or download files'
		);
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
					'username_password' => $this->trans( 'Username and password' ),
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
				'help'       => $this->trans(
					'If your private key is password protected, you can fill in that password here'
				),
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

	/**
	 * @param  array  $config
	 *
	 * @return ?seclibSFTP
	 */
	public function getClient( array $config ): ?object
	{
		$host     = $this->getRequestUrl( $config );
		$password = $this->getPassword( $config );

		$client = new seclibSFTP( $host, $config['port'] ?? 22 );

		$login = $client->login( $config['username'], $password );

		if ( ! $login ) {
			throw new AuthResultException( $this->trans( 'Cannot login to {host}', [ 'host' => $host ] ) );
		}

		return $client;
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

	/**
	 * @param seclibSFTP $client
	 * @param $filename
	 * @param $resource
	 *
	 * @return bool
	 */
	public function _get( $client, $filename, $resource )
	{
		try {
			return $client->get( $filename, $resource );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	/**
	 * @param seclibSFTP $client
	 * @param $filename
	 * @param $resource
	 *
	 * @return bool
	 */
	public function _put( $client, $filename, $resource )
	{
		try {
			if ( ! is_resource( $resource ) ) {
				$resource = $this->writeTmpFile( $resource );
			}

			return $client->put( $filename, $resource, FTP_BINARY );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	/**
	 * @param seclibSFTP $client
	 * @param $filename
	 *
	 * @return bool
	 */
	public function _delete( $client, $filename )
	{
		try {
			return $client->delete( $filename );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	/**
	 * @param seclibSFTP $client
	 * @param $directory
	 *
	 * @return false|array
	 */
	public function _nlist( $client, $directory = '.' ): false|array
	{
		try {
			return $client->nlist( $directory );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	/**
	 * @param seclibSFTP $client
	 * @param $directory
	 *
	 * @return false|string
	 */
	public function _mkdir( $client, $directory )
	{
		try {
			return $client->mkdir( $directory );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	/**
	 * @param seclibSFTP $client
	 * @param $directory
	 *
	 * @return bool
	 */
	public function _rmdir( $client, $directory )
	{
		try {
			return $client->rmdir( $directory );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	/**
	 * @param seclibSFTP $client
	 * @param $directory
	 * @param $type
	 *
	 * @return array
	 */
	public function _list( $client, $directory = '.', $type = null )
	{
		try {
			$rawFiles = $client->rawlist( $directory );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}

		$typeToNumbers = [ 'dir' => 2, 'file' => 1 ];
		$files         = [];
		foreach ( $rawFiles as $file ) {
			$name = $file['filename'];

			// Remove invalid files.
			if ( '.' === $name || '..' === $name ) {
				continue;
			}

			// Validate file type.
			if ( $type && $file['type'] !== $typeToNumbers[ $type ] ) {
				continue;
			}

			$files[] = $directory . DIRECTORY_SEPARATOR . $file['filename'];
		}

		return $files;
	}

	/**
	 * @throws ResultException
	 *
	 * @param seclibSFTP $client
	 * @param $from
	 * @param $to
	 * @param $override
	 *
	 * @return bool
	 */
	public function _rename( $client, $from, $to, $override = false )
	{
		try {
			if ( $override && $client->file_exists( $to ) ) {
				$this->_delete( $client, $to );
			}

			// Returns false if already exists.
			$success = $client->rename( $from, $to );

		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}

		if ( false === $success ) {
			throw new ResultException( 'Failed to rename, file exists.' );
		}

		return $success;
	}
}
