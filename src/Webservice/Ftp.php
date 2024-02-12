<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;

class Ftp extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'ftp';
		$this->name        = $this->trans( 'FTP' );
		$this->description = $this->trans( 'Connect to an FTP server to upload and/or download files' );
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
				'label' => $this->trans( 'Passive' ),
				'type'  => 'checkbox',
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
				'label'       => $this->trans( 'Path' ),
				'type'        => 'text',
				'placeholder' => './',
			],
		];
	}

	public function getRetrieveFields( array $defaults = [] ): array
	{
		return array_merge( parent::getRetrieveFields( $defaults ), [
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
		] );
	}

	public function getSendFields( array $defaults = [] ): array
	{
		return array_merge( parent::getSendFields( $defaults ), [
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
				'label'  => $this->trans( 'Filename' ),
				'type'   => 'text',
				'fields' => [
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
			'dirname' => [
				'label'      => $this->trans( 'Directory name' ),
				'type'       => 'text',
				'conditions' => [
					'action' => [ 'mkdir', 'rmdir' ],
				],
			],
		] );
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	/**
	 * @throws \Exception
	 *
	 * @param  array  $config
	 *
	 * @return \FTP\Connection|null
	 */
	public function getClient( array $config ): ?object
	{
		$host = $this->getRequestUrl( $config );
		$client = ftp_connect( $host, $config['port'] ?? 21 ) or throw new \Exception( 'Cannot connect to ' . $host );
		$login = ftp_login( $client, $config['username'] ?? '', $config['password'] ?? '' );

		if ( ! $login ) {
			throw new \Exception( $this->trans( 'Cannot login to {host}', [ 'host' => $host ] ) );
		}

		ftp_pasv( $client, ! empty( $config['passive'] ) );

		return $client;
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

		throw new \Exception( $this->trans( 'No action configured' ) );
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

		throw new \Exception( $this->trans( 'No action configured' ) );
	}

	public function getFile( $config, $client ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( $this->trans( 'No filename configured' ) );
		}

		$path    = $config['path'] ?? '.';
		$file    = $path . '/' . $config['filename'];
		$tmpFile = $this->createTmpFile( $config['filename'] );
		$result  = [];

		$success = $this->_get( $client, $file, $tmpFile );

		if ( ! $success ) {
			if ( empty( $config['passive'] ) ) {
				$message = $this->trans( 'Cannot fetch file from {host}, please try passive mode.', [ 'host' => $config['host'] ] );
			} else {
				$message = $this->trans( 'Cannot fetch file from {host}', [ 'host' => $config['host'] ] );
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

		return new Result( $result, $this->trans( 'Successfully retrieved: {name}', [ 'name' => $file ] ) );
	}

	public function sendFile( array $config, $data ): Result
	{
		$client = $this->getClient( $config );

		$filecontent = $this->encodeFormat( $config['format'] ?? '', $data );
		$response    = [];

		$filename = $config['filename'];
		if ( empty( $config['override'] ) ) {
			$directory = $this->getDirectory( $config, $client );
			$originalFilename = $filename;
			$filename  = $this->createUniqueFilename( $filename, $directory['files'] );
			if ( $filename !== $originalFilename ) {
				$response[] = $this->trans( 'File {oldName} existed, renamed to {newName}', [ 'oldName' => $originalFilename, 'newName' => $filename ] );
			}
		}

		$local_file = $this->createTmpFile();
		fwrite( $local_file, $filecontent );
		rewind( $local_file );

		$success = $this->_put( $client, $config['path'] . DIRECTORY_SEPARATOR . $filename, $local_file );

		$this->removeTmpFile( $local_file );

		if ( ! $success ) {
			throw new \Exception( $this->trans( 'Could not be write file to the server' ) );
		}

		if ( ! empty( $response ) ) {
			$response[] = $this->trans( 'Successfully uploaded: {name}', [ 'name' => $config['path'] . '/' . $filename ] );
		} else {
			$response = $this->trans( 'Successfully uploaded: {name}', [ 'name' => $config['path'] . '/' . $filename ] );
		}

		return new Result( true, $response );
	}

	public function deleteFile( $config ): Result
	{
		$client = $this->getClient( $config );

		$file    = $config['path'] . DIRECTORY_SEPARATOR . $config['filename'];
		$success = $this->_delete( $client, $file );

		if ( ! $success ) {
			throw new \Exception( $this->trans( 'Could not delete file from the server' ) );
		}

		return new Result( true, $this->trans( 'Successfully deleted: {name}', [ 'name' => $file ] ) );
	}

	public function getDirectory( $config, $client = null ): Result
	{
		$files = $this->_nlist( $client, $config['path'] ?? '.' );

		if ( ! is_array( $files ) ) {
			if ( empty( $config['passive'] ) ) {
				$message = $this->trans( 'Cannot read directory on {host}, please try passive mode.', [ 'host' => $config['host'] ] );
			} else {
				$message = $this->trans( 'Cannot read directory on {host}', [ 'host' => $config['host'] ] );
			}
			throw new \Exception( $message );
		}

		return new Result( $files, $this->trans( 'Successfully retrieved: {name}', [ 'name' => $config['path'] ] ) );
	}

	public function createDirectory( $config ): Result
	{
		if ( empty( $config['dirname'] ) ) {
			throw new \Exception( $this->trans( 'No directory configured' ) );
		}

		$path = $config['path'] ?? '';
		$dir  = $path . DIRECTORY_SEPARATOR . $config['dirname'];

		$success = $this->_mkdir( $this->getClient( $config ), $dir );

		if ( ! $success ) {
			throw new \Exception( $this->trans( 'Could not create directory: {dir}', [ 'dir' => $dir ] ) );
		}

		return new Result( true, $this->trans( 'Successfully created directory: {dir}', [ 'dir' => $dir ] ) );
	}

	public function deleteDirectory( $config ): Result
	{
		if ( empty( $config['dirname'] ) ) {
			throw new \Exception( $this->trans( 'No directory configured' ) );
		}

		$path = $config['path'] ?? '';
		$dir  = $path . DIRECTORY_SEPARATOR . $config['dirname'];

		$success = $this->_rmdir( $this->getClient( $config ), $dir );

		if ( ! $success ) {
			throw new \Exception( $this->trans( 'Could not remove directory: {dir}', [ 'dir' => $dir ] ) );
		}

		return new Result( true, $this->trans( 'Successfully removed directory: {dir}', [ 'dir' => $dir ] ) );
	}

	public function createUniqueFilename( $filename, $existing ): string
	{
		$ext           = pathinfo( $filename, PATHINFO_EXTENSION );
		$file_basename = basename( $filename, '.' . $ext );

		$x = 1;
		while ( $x <= 999 ) {
			$newFilename = $file_basename . $x . '.' . $ext;

			if ( ! in_array( $newFilename, $existing ) ) {
				$filename = $newFilename;
				break;
			}
			$x ++;
		}

		return $filename;
	}

	public function createTmpFile( $filename = '', $mode = 'w+' )
	{
		if ( $filename ) {
			$filename = tempnam( sys_get_temp_dir(), $filename );
		} else {
			$filename = 'php://temp';
		}

		return fopen( $filename, $mode );
	}

	public function removeTmpFile( $filename ): bool
	{
		if ( is_resource( $filename ) ) {
			// Get file name.
			$filename = $this->getResourcePath( $filename );
		}

		if ( is_file( $filename ) ) {
			return unlink( $filename );
		}

		return false;
	}

	public function getResourcePath( $resource ): string
	{
		if ( ! is_resource( $resource ) ) {
			throw new \Exception( $this->trans( 'Invalid resource' ) );
		}
		return stream_get_meta_data( $resource )['uri'];
	}

	public function _get( $client, $filename, $tmpFile )
	{
		try {
			$file = ftp_fget( $client, $tmpFile, $filename );
		} catch ( \Exception $e ) {
			return false;
		}

		return $file;
	}

	public function _put( $client, $filename, $local_file,  )
	{
		return ftp_fput( $client, $filename, $local_file, FTP_BINARY );
	}

	public function _delete( $client, $filename )
	{
		try {
			ftp_delete( $client, $filename );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	public function _nlist( $client, $directory = '.' ): array|false
	{
		return ftp_nlist( $client, $directory );
	}

	{
		try {
			ftp_mkdir( $client, $directory );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	public function _rmdir( $client, $directory )
	{
		try {
			ftp_rmdir( $client, $directory );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}
}
