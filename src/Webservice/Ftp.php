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
				'label'       => 'Path',
				'type'        => 'text',
				'placeholder' => './',
			],
		];
	}

	public function getRetrieveFields( array $defaults = [] ): array
	{
		return array_merge( parent::getRetrieveFields( $defaults ), [
			'action'   => [
				'label'   => 'Select what you want to retrieve',
				'type'    => 'select',
				'choices' => [
					'get'  => 'File contents',
					'list' => 'Directory filenames',
				],
			],
			'filename' => [
				'label'      => 'Filename',
				'type'       => 'text',
				'conditions' => [
					'action' => 'file',
				],
				'fields'     => [
					'format' => $this->getFormatDecodeField(),
				],
			],
		] );
	}

	public function getSendFields( array $defaults = [] ): array
	{
		return array_merge( parent::getRetrieveFields( $defaults ), [
			'action'   => [
				'label'   => 'Select what action you want to send',
				'type'    => 'select',
				'choices' => [
					'put'    => 'Upload file contents',
					'delete' => 'Delete file',
					'mkdir'  => 'Create directory',
					'rmdir'  => 'Delete directory',
				],
			],
			'filename' => [
				'label'  => 'Filename',
				'type'   => 'text',
				'fields' => [
					'format'   => $this->getFormatEncodeField(),
					'override' => [
						'label' => 'Overwrite if file exists',
						'type'  => 'boolean',
					],
				],
				/*'conditions' => [
					'action' => 'file',
				],*/
			],
		] );
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	public function getClient( array $config )
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
			case 'list':
			case 'dir':
				$result = $this->getDirectory( $config, $client );
			break;
			case 'file':
			case 'get':
				$result = $this->getFile( $config, $client );
			break;
		}

		if ( isset( $result ) ) {
			return new Result( $result['files'], $result['response'] ?? null );
		}

		throw new \Exception( $this->trans( 'No action configured' ) );
	}

	public function send( array $config, $data ): Result
	{
		//@todo make the file/directory names variable with data
		switch ( $config['action'] ) {
			case 'file':
			case 'put':
				$result = $this->sendFile( $config, $data );
			break;
			case 'mkdir':
				$result = $this->createDirectory( $config, $data );
			break;
			case 'delete':
				$result = $this->deleteFile( $config );
			break;
			case 'rmdir':
				$result = $this->deleteDirectory( $config, $data );
			break;
		}
		if ( isset( $result ) ) {
			return new Result( $data, $result['response'] ?? null );
		}

		throw new \Exception( $this->trans( 'No action configured' ) );
	}

	public function getFile( $config, $client )
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( $this->trans( 'No filename configured' ) );
		}

		$path    = $config['path'] ?? '.';
		$file    = $path . '/' . $config['filename'];
		$tmpFile = $this->createTmpFile( $config['filename'] );
		$result  = [];

		$success = $this->fetchFile( $file, $tmpFile, $client );

		if ( ! $success ) {
			if ( empty( $config['passive'] ) ) {
				$message = $this->trans( 'Cannot fetch file from {host}, please try passive mode.', [ 'host' => $config['host'] ] );
			} else {
				$message = $this->trans( 'Cannot fetch file from {host}', [ 'host' => $config['host'] ] );
			}
			throw new \Exception( $message );
		} else {
			$result['response'] = $this->trans( 'Successfully retrieved: {name}', [ 'name' => $file ] );
		}

		// Get file path/name.
		$tmpFileName = $this->getResourcePath( $tmpFile );

		// Get file contents.
		$result['files'] = file_get_contents( $tmpFileName );

		try {
			if ( ! empty( $config['format'] ) ) {
				if ( $result['files'] ) {
					$result['files'] = $this->decodeFormat( $config['format'], $result['files'], $config );
				} else {
					// Try to decode from file.
					$result['files'] = $this->decodeFormat( $config['format'], $tmpFileName, $config );
				}
			}
		} catch ( \Throwable $e ) {
			$this->removeTmpFile( $tmpFileName );
			fclose( $tmpFile );
			throw $e;
		}

		$this->removeTmpFile( $tmpFileName );
		fclose( $tmpFile );

		return $result;
	}

	public function sendFile( array $config, $data ): mixed
	{
		$client = $this->getClient( $config );

		$filecontent        = $this->encodeFormat( $config['format'] ?? '', $data );
		$result['response'] = [];

		$filename = $config['filename'];
		if ( empty( $config['override'] ) ) {
			$directory = $this->getDirectory( $config, $client );
			$filename  = $this->createUniqueFilename( $filename, $directory['files'] );
			$result['response'][] = $directory['response'] . ' ' . $this->trans( 'to create unique filename' );
		}

		$local_file = $this->createTmpFile();
		fwrite( $local_file, $filecontent );
		rewind( $local_file );

		$upload_result = $this->putFile( $client, $config, $local_file, $filename );

		$this->removeTmpFile( $local_file );

		if ( ! $upload_result ) {
			throw new \Exception( $this->trans( 'Could not be write file to the server' ) );
		} else {
			$result['response'][] = $this->trans( 'Successfully uploaded: {name}', [ 'name' => $config['path'] . '/' . $filename ] );
		}

		return $result;
	}

	public function deleteFile( $config )
	{
		$client = $this->getClient( $config );

		$file          = $config['path'] . "/" . $config['filename'];
		$delete_result = $this->deleteSingleFile( $client, $file );

		$result = [];

		if ( ! $delete_result ) {
			throw new \Exception( $this->trans( 'Could not delete file from the server' ) );
		} else {
			$result['response'] = $this->trans( 'Successfully deleted: {name}', [ 'name' => $file ] );
		}

		return $result;
	}

	public function deleteSingleFile( $client, $file )
	{
		try {
			ftp_delete( $client, $file );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	public function createDirectory($config, $data)
	{
		$client = $this->getClient( $config );
		try {
			ftp_mkdir( $client, $config['filename'] );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	public function deleteDirectory( $config, $data )
	{
		$client = $this->getClient( $config );
		try {
			ftp_rmdir( $client, $config['filename'] );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	public function fetchFile( $file, $tmpFile, $client )
	{
		try {
			$file = ftp_fget( $client, $tmpFile, $file  );
		} catch ( \Exception $e ) {
			return false;
		}

		return $file;
	}

	public function putFile( $client, $config, $local_file, $filename )
	{
		ftp_chdir( $client, $config['path'] );
		$upload_result = ftp_fput( $client, $filename, $local_file, FTP_BINARY );
		ftp_close( $client );

		return $upload_result;
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

	public function removeTmpFile( $filename ): void
	{
		if ( is_resource( $filename ) ) {
			// Get file name.
			$filename = $this->getResourcePath( $filename );
		}
		if ( is_file( $filename ) ) {
			unlink( $filename );
		}
	}

	public function getResourcePath( $resource ): string
	{
		return stream_get_meta_data( $resource )['uri'];
	}

	public function getDirectory( $config, $client = null ): array
	{
		$result['files'] = $this->listDirectory( $client, $config );

		if ( ! is_array( $result['files'] ) ) {
			if ( empty( $config['passive'] ) ) {
				$message = $this->trans( 'Cannot read directory on {host}, please try passive mode.', [ 'host' => $config['host'] ] );
			} else {
				$message = $this->trans( 'Cannot read directory on {host}', [ 'host' => $config['host'] ] );
			}
			throw new \Exception( $message );
		} else {
			$result['response'] = $this->trans( 'Successfully retrieved: {name}', [ 'name' => $config['path'] ] );
		}

		return $result;
	}

	public function listDirectory( $client, $config )
	{
		return ftp_nlist( $client, $config['path'] ?? '.' );
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
}
