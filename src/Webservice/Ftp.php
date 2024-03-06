<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Helper\ResultException;
use SyncEngine\Webservice\Trait\Client;
use SyncEngine\Webservice\Trait\Files;

class Ftp extends WebserviceModel
{
	use Files;
	use Client;

	public function __construct()
	{
		parent::__construct();

		$this->type        = 'ftp';
		$this->name        = $this->trans( 'FTP' );
		$this->description = $this->trans( 'Connect to an FTP server to upload and/or download files' );
	}

	public function getFields( array $defaults = [] ): array
	{
		return $this->getRequestFields( $defaults );
	}

	public function getRequestFields( array $defaults = [] ): array
	{
		return [
			'path' => [
				'label'       => $this->trans( 'Path' ),
				'type'        => 'text',
				'placeholder' => './',
			],
		];
	}

	public function getRetrieveFields( array $defaults = [] ): array
	{
		return array_merge(
			parent::getRetrieveFields( $defaults ),
			[
				'action'   => [
					'label'   => $this->trans( 'Select what you want to retrieve' ),
					'type'    => 'select',
					'choices' => [
						'get'  => $this->trans( 'File contents' ),
						'list' => $this->trans( 'Directory filenames' ),
					],
				],
				'filename' => [
					'label'      => $this->trans( 'Filename' ),
					'type'       => 'text',
					'fields'     => [
						'format' => $this->getFormatDecodeField(),
					],
					'conditions' => [
						'action' => 'get',
					],
				],
			],
		);
	}

	public function getSendFields( array $defaults = [] ): array
	{
		return array_merge(
			parent::getSendFields( $defaults ),
			[
				'action'   => [
					'label'   => $this->trans( 'Select what action you want to send' ),
					'type'    => 'select',
					'choices' => [
						'put'    => $this->trans( 'Upload file contents' ),
						'delete' => $this->trans( 'Delete file' ),
						'mkdir'  => $this->trans( 'Create directory' ),
						'rmdir'  => $this->trans( 'Delete directory' ),
					],
				],
				'filename' => [
					'label'      => $this->trans( 'Filename' ),
					'type'       => 'text',
					'fields'     => [
						'format'   => $this->getFormatEncodeField(),
						'override' => [
							'label' => $this->trans( 'Overwrite if file exists' ),
							'type'  => 'boolean',
						],
					],
					'conditions' => [
						'action' => [ 'put', 'delete' ],
					],
				],
				'dirname'  => [
					'label'      => $this->trans( 'Directory name' ),
					'type'       => 'text',
					'conditions' => [
						'action' => [ 'mkdir', 'rmdir' ],
					],
				],
			]
		);
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
						'Could not connected to {host}',
						[ 'host' => $this->getRequestUrl( $config ) ]
					),
					'Error'   => $e->getMessage(),
				],
				'Config' => $config,
			]
			);
		}
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
		$cache = $this->fetchClient( $config['connection']?->getRef() );
		if ( $cache ) {
			return $cache;
		}

		$host = $this->getRequestUrl( $config );

		try {
			if ( ! empty( $config['ssl'] ) ) {
				$client = ftp_ssl_connect( $host, $config['port'] ?? 21, $config['timeout'] ?? 10 );
			} else {
				$client = ftp_connect( $host, $config['port'] ?? 21, $config['timeout'] ?? 10 );
			}
		} catch ( \Exception $e ) {
			$client = $e;
		}

		if ( ! $client || $client instanceof \Exception ) {
			$error = $this->trans( 'Cannot connect to {host}', [ 'host' => $host ] );
			if ( $client instanceof \Exception ) {
				$error .= ': ' . $client->getMessage();
			}
			throw new \Exception( $error );
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
			}
			throw new \Exception( $error );
		}

		ftp_pasv( $client, ! empty( $config['passive'] ) );

		$this->client = $client;
		$this->cacheClient( $client, $config['connection']?->getRef() );

		return $client;
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$client = $this->getClient( $config );

		switch ( $config['action'] ?? '' ) {
			case 'file':
			case 'get':
				return $this->getFile( $client, $config );

			case 'list':
			case 'dir':
				return $this->getDirectory( $client, $config );
		}

		throw new \Exception( $this->trans( 'No action configured' ) );
	}

	public function getFile( $client, $config ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( $this->trans( 'No Filename configured' ) );
		}

		$file    = $this->getFullPath( $config['filename'], $config['path'] ?? '' );
		$tmpFile = $this->createTmpFile( $config['filename'] );

		$success = $this->_get( $client, $file, $tmpFile );

		if ( ! $success ) {
			$fields = $this->getAuthFields();
			if ( isset( $fields['passive'] ) && empty( $config['passive'] ) ) {
				$message = $this->trans(
					'Cannot fetch file from {host}, please try passive mode',
					[ 'host' => $config['host'] ]
				);
			} else {
				$message = $this->trans(
					'Cannot fetch file from {host}',
					[ 'host' => $config['host'] ]
				);
			}
			throw new \Exception( $message );
		}

		// Get file path/name.
		$tmpFileName = $this->getResourcePath( $tmpFile );

		// Get file contents.
		$result = file_get_contents( $tmpFileName );

		try {
			if ( ! empty( $config['format'] ) ) {
				if ( $result ) {
					$result = $this->decodeFormat( $config['format'], $result, $config );
				} else {
					// Try to decode from file.
					$result = $this->decodeFormat( $config['format'], $tmpFileName, $config );
				}
			}
		} catch ( \Throwable $e ) {
			$this->removeTmpFile( $tmpFileName );
			fclose( $tmpFile );
			throw $e;
		}

		$this->removeTmpFile( $tmpFileName );
		fclose( $tmpFile );

		return new Result(
			$result, $this->trans( 'Successfully retrieved: {name}', [ 'name' => $file ] ), $config
		);
	}

	public function _get( $client, $filename, $tmpFile )
	{
		try {
			return ftp_fget( $client, $tmpFile, $filename );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
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
				'label'   => $this->trans( 'Timeout in seconds' ),
				'type'    => 'number',
				'default' => 10,
			],
		];
	}

	public function getDirectory( $client, $config ): Result
	{
		$path  = $this->getFullPath( "", $config['path'] ?? '' );
		$files = $this->_nlist( $client, $path ?: '.' );

		if ( ! is_array( $files ) ) {
			$fields = $this->getAuthFields();
			if ( isset( $fields['passive'] ) && empty( $config['passive'] ) ) {
				$message = $this->trans(
					'Cannot directory from {host}, please try ftp passive mode',
					[ 'host' => $config['host'] . $path ]
				);
			} else {
				$message = $this->trans(
					'Cannot read directory from {host}',
					[ 'host' => $config['host'] . $path ]
				);
			}
			throw new \Exception( $message );
		}

		return new Result(
			$files, $this->trans( 'Successfully retrieved: {name}', [ 'name' => $path ] ), $config
		);
	}

	public function _nlist( $client, $directory = '.' )
	{
		try {
			return ftp_nlist( $client, $directory );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	public function send( array $config, $data ): Result
	{
		$client = $this->getClient( $config );

		//@todo make the file/directory names variable with data
		switch ( $config['action'] ) {
			case 'file':
			case 'put':
				return $this->sendFile( $client, $config, $data );

			case 'delete':
				return $this->deleteFile( $client, $config );

			case 'mkdir':
				return $this->createDirectory( $client, $config );

			case 'rmdir':
				return $this->deleteDirectory( $client, $config );
		}

		throw new \Exception( $this->trans( 'No action configured' ) );
	}

	public function sendFile( $client, array $config, $data ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( $this->trans( "No Filename configured" ) );
		}

		$response    = [];
		$content     = $this->encodeFormat( $config['format'] ?? '', $data );
		$remote_file = $this->getFullPath( $config['filename'], $config['path'] ?? '' );

		if ( empty( $config['override'] ) ) {
			$filepath         = dirname( $remote_file );
			$originalFilename = basename( $remote_file );

			$directory = $this->getDirectory( $filepath ?? '.', $client );
			$filename  = $this->createUniqueFilename( $originalFilename, $directory->getData() );

			if ( $filename !== $originalFilename ) {
				$response[] = $this->trans(
					'File {oldName} existed, renamed it to {newName}',
					[ 'oldName' => $originalFilename, 'newName' => $filename ]
				);

				$remote_file = $this->getFullPath( $filename, $filepath );
			}
		}

		$local_file = $this->createTmpFile();
		fwrite( $local_file, $content );
		rewind( $local_file );

		$success = $this->_put( $client, $remote_file, $local_file );

		$this->removeTmpFile( $local_file );

		if ( ! $success ) {
			throw new \Exception( $this->trans( 'Could not be write file to the server' ) );
		}

		if ( ! empty( $response ) ) {
			$response[] = $this->trans( 'Successfully uploaded: {name}', [ 'name' => $remote_file ] );
		} else {
			$response = $this->trans( 'Successfully uploaded: {name}', [ 'name' => $remote_file ] );
		}

		return new Result( true, $response, $config );
	}

	public function _put( $client, $filename, $local_file )
	{
		try {
			return ftp_fput( $client, $filename, $local_file, FTP_BINARY );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	public function deleteFile( $client, $config ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( $this->trans( 'No Filename configured' ) );
		}

		$file    = $this->getFullPath( $config['filename'], $config['path'] ?? '' );
		$success = $this->_delete( $client, $file );

		if ( ! $success ) {
			throw new \Exception(
				$this->trans( 'Could not delete {name} from the server', [ 'name' => 'file' ] )
			);
		}

		return new Result(
			true,
			$this->trans( 'Could not delete {name} from the server', [ 'name' => $file ] ),
			$config
		);
	}

	public function _delete( $client, $filename )
	{
		try {
			return ftp_delete( $client, $filename );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	public function createDirectory( $client, $config ): Result
	{
		if ( empty( $config['dirname'] ) ) {
			throw new \Exception( $this->trans( 'No directory configured' ) );
		}

		$dir = $this->getFullPath( $config['dirname'], $config['path'] ?? '' );

		$success = $this->_mkdir( $client, $dir );

		if ( ! $success ) {
			throw new \Exception(
				$this->trans( 'Could not create directory: {dir}', [ 'dir' => $dir ] )
			);
		}

		return new Result(
			true, $this->trans( 'Successfully created directory: {dir}', [ 'dir' => $dir ] )
		);
	}

	public function _mkdir( $client, $directory )
	{
		try {
			return ftp_mkdir( $client, $directory );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	public function deleteDirectory( $client, $config ): Result
	{
		if ( empty( $config['dirname'] ) ) {
			throw new \Exception( $this->trans( 'No directory configured' ) );
		}

		$dir = $this->getFullPath( $config['dirname'], $config['path'] ?? '' );

		$success = $this->_rmdir( $client, $dir );

		if ( ! $success ) {
			throw new \Exception(
				$this->trans( 'Could not delete {name} from server', [ 'name' => 'dir' ] )
			);
		}

		return new Result( true, $this->trans( 'Successfully deleted: {name}', [ 'name' => $dir ] ) );
	}

	public function _rmdir( $client, $directory )
	{
		try {
			return ftp_rmdir( $client, $directory );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}
}
