<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Helper\ResultException;
use SyncEngine\Webservice\Trait\Files;

class Ftp extends WebserviceModel
{
	use Files;

	public function __construct()
	{
		parent::__construct();

		$this->type        = 'ftp';
		$this->name        = $this->trans( 'FTP', [], "webservice/ftp" );
		$this->description = $this->trans(
			'Connect to an FTP server to upload and/or download files',
			[],
			"webservice/ftp"
		);
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => $this->trans( 'Host', [], "webservice/ftp" ),
				'type'  => 'text',
			],
			'port'     => [
				'label'   => $this->trans( 'Port', [], "webservice/ftp" ),
				'type'    => 'number',
				'default' => 21,
			],
			'username' => [
				'label' => $this->trans( 'Username', [], "webservice/ftp" ),
				'type'  => 'secret',
			],
			'password' => [
				'label' => $this->trans( 'Password', [], "webservice/ftp" ),
				'type'  => 'secret',
			],
			'passive'  => [
				'label' => $this->trans( 'Passive mode', [], "webservice/ftp" ),
				'type'  => 'checkbox',
			],
			'ssl'      => [
				'label'   => $this->trans( 'Connect using SSL', [], "webservice/ftp" ),
				'type'    => 'checkbox',
				'default' => true,
			],
		];
	}

	public function getFields( array $defaults = [] ): array
	{
		return $this->getRequestFields( $defaults );
	}

	public function getRequestFields( array $defaults = [] ): array
	{
		return [
			'path' => [
				'label'       => $this->trans( 'Path', [], "webservice/ftp" ),
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
					'label'   => $this->trans( 'Select what you want to retrieve', [], "webservice/ftp" ),
					'type'    => 'select',
					'choices' => [
						'get'  => $this->trans( 'File contents', [], "webservice/ftp" ),
						'list' => $this->trans( 'Directory filenames', [], "webservice/ftp" ),
					],
				],
				'filename' => [
					'label'      => $this->trans( 'Filename', [], "webservice/ftp" ),
					'type'       => 'text',
					'fields'     => [
						'format' => $this->getFormatDecodeField(),
					],
					'conditions' => [
						'action' => 'get',
					],
				]
			],
		);
	}

	public function getSendFields( array $defaults = [] ): array
	{
		return array_merge(
			parent::getSendFields( $defaults ),
			[
				'action'   => [
					'label'   => $this->trans( 'Select what action you want to send', [], "webservice/ftp" ),
					'type'    => 'select',
					'choices' => [
						'put'    => $this->trans( 'Upload file contents', [], "webservice/ftp" ),
						'delete' => $this->trans( 'Delete file', [], "webservice/ftp" ),
						'mkdir'  => $this->trans( 'Create directory', [], "webservice/ftp" ),
						'rmdir'  => $this->trans( 'Delete directory', [], "webservice/ftp" ),
					],
				],
				'filename' => [
					'label'      => $this->trans( 'Filename', [], "webservice/ftp" ),
					'type'       => 'text',
					'fields'     => [
						'format'   => $this->getFormatEncodeField(),
						'override' => [
							'label' => $this->trans( 'Overwrite if file exists', [], "webservice/ftp" ),
							'type'  => 'boolean',
						],
					],
					'conditions' => [
						'action' => [ 'put', 'delete' ],
					],
				],
				'dirname'  => [
					'label'      => $this->trans( 'Directory name', [], "webservice/ftp" ),
					'type'       => 'text',
					'conditions' => [
						'action' => [ 'mkdir', 'rmdir' ],
					],
				],
			]
		);
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
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
		$host = $this->getRequestUrl( $config );

		try {
			if ( ! empty( $config['ssl'] ) ) {
				$client = ftp_ssl_connect( $host, $config['port'] ?? 21 );
			} else {
				$client = ftp_connect( $host, $config['port'] ?? 21 );
			}
		} catch ( \Exception $e ) {
			$client = $e;
		}

		if ( ! $client || $client instanceof \Exception ) {
			$error = $this->trans( 'Cannot connect to {host}', [ 'host' => $host ], "webservice/ftp" );
			if ( $client instanceof \Exception ) {
				$error .= ': ' . $client->getMessage();
			}
			throw new \Exception( $error );
		}

		try {
			$login = @ftp_login( $client, $config['username'] ?? '', $config['password'] ?? '' );
		} catch ( \Exception $e ) {
			$client = $e;
		}

		if ( ! $login || $client instanceof \Exception ) {
			$error = $this->trans( 'Cannot login to {host}', [ 'host' => $host ], "webservice/ftp" );
			if ( $client instanceof \Exception ) {
				$error .= ': ' . $client->getMessage();
			}
			throw new \Exception( $error );
		}

		ftp_pasv( $client, ! empty( $config['passive'] ) );

		return $client;
	}

	public function connect( array $config ): Result
	{
		try {
			$this->getClient( $config );

			return new Result( true, true, [
				'Message' =>$this->trans( 'Successfully connected to {host}', [ 'host' => $this->getRequestUrl( $config ) ], "webservice" ),
				'Config' => $config,
			] );

		} catch ( \Exception $e ) {
			return new Result( false, false, [
				'Error' => [
					'Message' => $this->trans( 'Could not connected to {host}', [ 'host' => $this->getRequestUrl( $config ) ], "webservice" ),
					'Error' => $e->getMessage(),
				],
				'Config' => $config,
			] );
		}
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$client = $this->getClient( $config );

		switch ( $config['action'] ?? '' ) {
			case 'file':
			case 'get':
				return $this->getFile( $config, $client );

			case 'list':
			case 'dir':
				return $this->getDirectory( $config, $client );
		}

		throw new \Exception( $this->trans( 'No action configured', [], "webservice/ftp" ) );
	}

	public function send( array $config, $data ): Result
	{
		//@todo make the file/directory names variable with data
		switch ( $config['action'] ) {
			case 'file':
			case 'put':
				return $this->sendFile( $config, $data );

			case 'delete':
				return $this->deleteFile( $config );

			case 'mkdir':
				return $this->createDirectory( $config );

			case 'rmdir':
				return $this->deleteDirectory( $config );
		}

		throw new \Exception( $this->trans( 'No action configured', [], "webservice/ftp" ) );
	}

	public function getFile( $config, $client ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( $this->trans( 'No Filename configured', [], "webservice/ftp" ) );
		}

		$file    = $this->getFullPath( $config['filename'], $config['path'] ?? '' );
		$tmpFile = $this->createTmpFile( $config['filename'] );

		$success = $this->_get( $client, $file, $tmpFile );

		if ( ! $success ) {
			$fields = $this->getAuthFields();
			if ( isset( $fields['passive'] ) && empty( $config['passive'] ) ) {
				$message = $this->trans(
					'Cannot fetch file from {host}, please try passive mode',
					[ 'host' => $config['host'] ],
					"webservice/ftp"
				);
			} else {
				$message = $this->trans(
					'Cannot fetch file from {host}',
					[ 'host' => $config['host'] ],
					"webservice/ftp"
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
			$result, $this->trans( 'Successfully retrieved: {name}', [ 'name' => $file ], "webservice/ftp" )
		);
	}

	public function sendFile( array $config, $data ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( $this->trans( "No Filename configured", [], "webservice/ftp" ) );
		}

		$client      = $this->getClient( $config );
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
					[ 'oldName' => $originalFilename, 'newName' => $filename ],
					"webservice/ftp"
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
			throw new \Exception( $this->trans( 'Could not be write file to the server', [], "webservice/ftp" ) );
		}

		if ( ! empty( $response ) ) {
			$response[] = $this->trans( 'Successfully uploaded: {name}', [ 'name' => $remote_file ], "webservice/ftp" );
		} else {
			$response = $this->trans( 'Successfully uploaded: {name}', [ 'name' => $remote_file ], "webservice/ftp" );
		}

		return new Result( true, $response );
	}

	public function deleteFile( $config ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( $this->trans( 'No Filename configured', [], "webservice/ftp" ) );
		}

		$file    = $this->getFullPath( $config['filename'], $config['path'] ?? '' );
		$success = $this->_delete( $this->getClient( $config ), $file );

		if ( ! $success ) {
			throw new \Exception(
				$this->trans( 'Could not delete {name} from the server', [ 'name' => 'file' ], "webservice/ftp" )
			);
		}

		return new Result(
			true, $this->trans(
			'Could not delete {name} from the server',
			[ 'name' => $file ],
			"webservice/ftp"
		)
		);
	}

	public function getDirectory( $config, $client = null ): Result
	{
		$path  = $this->getFullPath( "", $config['path'] ?? '' );
		$files = $this->_nlist( $client, $path ?? '.' );

		if ( ! is_array( $files ) ) {
			$fields = $this->getAuthFields();
			if ( isset( $fields['passive'] ) && empty( $config['passive'] ) ) {
				$message = $this->trans(
					'Cannot directory from {host}, please try ftp passive mode',
					[ 'host' => $config['host'] ],
					"webservice/ftp"
				);
			} else {
				$message = $this->trans(
					'Cannot read directory from {host}',
					[ 'host' => $config['host'] ],
					"webservice/ftp"
				);
			}
			throw new \Exception( $message );
		}

		return new Result(
			$files, $this->trans( 'Successfully retrieved: {name}', [ 'name' => $path ], "webservice/ftp" )
		);
	}

	public function createDirectory( $config ): Result
	{
		if ( empty( $config['dirname'] ) ) {
			throw new \Exception( $this->trans( 'No directory configured', [], "webservice/ftp" ) );
		}

		$dir = $this->getFullPath( $config['dirname'], $config['path'] ?? '' );

		$success = $this->_mkdir( $this->getClient( $config ), $dir );

		if ( ! $success ) {
			throw new \Exception(
				$this->trans( 'Could not create directory: {dir}', [ 'dir' => $dir ], "webservice/ftp" )
			);
		}

		return new Result(
			true, $this->trans( 'Successfully created directory: {dir}', [ 'dir' => $dir ], "webservice/ftp" )
		);
	}

	public function deleteDirectory( $config ): Result
	{
		if ( empty( $config['dirname'] ) ) {
			throw new \Exception( $this->trans( 'No directory configured', [], "webservice/ftp" ) );
		}

		$dir = $this->getFullPath( $config['dirname'], $config['path'] ?? '' );

		$success = $this->_rmdir( $this->getClient( $config ), $dir );

		if ( ! $success ) {
			throw new \Exception(
				$this->trans( 'Could not delete {name} from server', [ 'name' => 'dir' ], "webservice/ftp" )
			);
		}

		return new Result( true, $this->trans( 'Successfully deleted: {name}', [ 'name' => $dir ], "webservice/ftp" ) );
	}

	public function _get( $client, $filename, $tmpFile )
	{
		try {
			return ftp_fget( $client, $tmpFile, $filename );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	public function _put( $client, $filename, $local_file )
	{
		try {
			return ftp_fput( $client, $filename, $local_file, FTP_BINARY );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	public function _delete( $client, $filename )
	{
		try {
			return ftp_delete( $client, $filename );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	public function _nlist( $client, $directory = '.' ): array|false
	{
		try {
			return ftp_nlist( $client, $directory );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
	}

	public function _mkdir( $client, $directory )
	{
		try {
			return ftp_mkdir( $client, $directory );
		} catch ( \ErrorException $e ) {
			throw new ResultException( $e );
		}
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
