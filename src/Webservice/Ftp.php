<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Exception\AuthResultException;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Trait\Client;
use SyncEngine\Webservice\Trait\ClientFiles;
use SyncEngine\Webservice\Type\FtpWebserviceType;

class Ftp extends WebserviceModel
{
	use ClientFiles;
	use Client;

	public function __construct()
	{
		parent::__construct();

		$this->type        = FtpWebserviceType::TYPE;
		$this->icon        = 'webservice-ftp';
		$this->name        = $this->trans( 'FTP' );
		$this->description = $this->trans( 'Connect to a FTP server to upload and/or download files' );
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => $this->trans( 'Host' ),
				'type'  => 'text',
			],
			'port'     => [
				'label'   => $this->trans( 'Port' ),
				'type'    => 'number',
				'default' => 21,
			],
			'username' => [
				'label' => $this->trans( 'Username' ),
				'type'  => 'secret',
			],
			'password' => [
				'label' => $this->trans( 'Password' ),
				'type'  => 'secret',
			],
			'passive'  => [
				'label' => $this->trans( 'Passive mode' ),
				'type'  => 'checkbox',
			],
			'ssl'      => [
				'label'   => $this->trans( 'Connect using SSL' ),
				'type'    => 'checkbox',
				'default' => true,
			],
			'timeout'  => [
				'label'   => $this->trans( 'Process timeout in seconds' ),
				'type'    => 'number',
				'default' => 10,
			],
		];
	}

	/**
	 * @todo Maybe use https://github.com/Nicolab/php-ftp-client
	 * @throws \Exception
	 *
	 * @param  array  $config
	 *
	 * @return \FTP\Connection|null
	 */
	public function getClient( array $config ): ?object
	{
		$timeout = (int) ( $config['timeout'] ?? 10 );

		$ref = $config['connection']?->getRef();
		if ( $ref ) {
			$cache = $this->fetchClient( $ref );
			if ( $cache && ( empty( $cache['exp'] ) || time() < $cache['exp'] ) ) {
				return $cache['client'];
			}
		}

		$host = $this->getRequestUrl( $config );

		try {
			if ( ! empty( $config['ssl'] ) ) {
				$client = ftp_ssl_connect( $host, (int) ( $config['port'] ?? 21 ), $timeout );
			} else {
				$client = ftp_connect( $host, (int) ( $config['port'] ?? 21 ), $timeout );
			}
		} catch ( \Exception $e ) {
			$client = $e;
		}

		if ( ! $client || $client instanceof \Exception ) {
			$error = $this->trans( 'Cannot connect to {host}', [ 'host' => $host ] );
			if ( $client instanceof \Exception ) {
				$error .= ': ' . $client->getMessage();
				throw new AuthResultException( $error, previous: $client );
			}
			throw new AuthResultException( $error );
		}

		try {
			$login = ftp_login( $client, $config['username'] ?? '', $config['password'] ?? '' );
		} catch ( \Exception $e ) {
			$login = $e;
		}

		if ( ! $login || $login instanceof \Exception ) {
			$error = $this->trans( 'Cannot login to {host}', [ 'host' => $host ] );
			if ( $login instanceof \Exception ) {
				$error .= ': ' . $login->getMessage();
				throw new AuthResultException( $error, previous: $login );
			}
			throw new AuthResultException( $error );
		}

		ftp_pasv( $client, ! empty( $config['passive'] ) );

		$this->client = $client;

		if ( $ref ) {
			$this->cacheClient( [ 'client' => $client, 'exp' => time() + $timeout ], $config['connection']?->getRef() );
		}

		return $client;
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	public function connect( array $config ): Result
	{
		try {
			$this->getClient( $config );

			return new Result(
				true, true, [
					    'Message' => $this->trans(
						    'Successfully connected to {host}',
						    [ 'host' => $this->getRequestUrl( $config ) ]
					    ),
					    'Config'  => $config,
				    ]
			);
		} catch ( \Exception $e ) {
			return new Result(
				false, false, [
					     'Error'  => [
						     'Message' => $this->trans(
							     'Could not connect to {host}',
							     [ 'host' => $this->getRequestUrl( $config ) ]
						     ),
						     'Error'   => $e->getMessage(),
					     ],
					     'Config' => $config,
				     ]
			);
		}
	}

	public function _get( $client, $filename, $resource )
	{
		return ftp_fget( $client, $resource, $filename );
	}

	public function _put( $client, $filename, $resource )
	{
		if ( ! is_resource( $resource ) ) {
			$resource = $this->writeTmpFile( $resource );
		}

		return ftp_fput( $client, $filename, $resource, FTP_BINARY );
	}

	public function _delete( $client, $filename )
	{
		return ftp_delete( $client, $filename );
	}

	public function _nlist( $client, $directory = '.' )
	{
		return ftp_nlist( $client, $directory );
	}

	public function _list( $client, $directory = '.', $type = null )
	{
		$rawFiles = ftp_mlsd( $client, $directory );

		$files = [];
		foreach ( $rawFiles as $file ) {

			// Skip invalid files.
			if ( 'cdir' === $file['type'] || 'pdir' === $file['type'] ) {
				continue;
			}

			// Validate file type.
			if ( $type && $file['type'] !== $type ) {
				continue;
			}

			$files[] = $directory . DIRECTORY_SEPARATOR . $file['name'];
		}

		return $files;
	}

	public function _mkdir( $client, $directory )
	{
		return ftp_mkdir( $client, $directory );
	}

	public function _rmdir( $client, $directory )
	{
		return ftp_rmdir( $client, $directory );
	}

	public function _rename( $client, $from, $to, $override = false )
	{
		if ( $override ) {
			$this->_delete( $client, $to );
		}

		return ftp_rename( $client, $from, $to );
	}
}
