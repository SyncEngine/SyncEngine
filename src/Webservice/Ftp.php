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
			'username' => [
				'label' => $this->trans( 'Username' ),
				'type'  => 'secret',
			],
			'password' => [
				'label' => $this->trans( 'Password' ),
				'type'  => 'secret',
			],
			'port'     => [
				'label'   => $this->trans( 'Port' ),
				'type'    => 'number',
				'default' => 21,
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
		return array_merge(
			parent::getRetrieveFields( $defaults ),
			[
				'action'    => [
					'label'   => 'Select what you want to retrieve',
					'type'    => 'select',
					'choices' => [
						'file' => 'File contents',
						'dir'  => 'Directory filenames',
					],
				],
				'filename' => [
					'label'      => 'Filename',
					'type'       => 'text',
					'conditions' => [
						'action'  => 'file',
					],
					'fields'     => [
						'format' => $this->getFormatDecodeField(),
					],
				],
			]
		);
	}

	public function getSendFields( array $defaults = [] ): array
	{
		return array_merge(
			parent::getRetrieveFields( $defaults ),
			[
				'action'    => [
					'label'   => 'Select what action you want to send',
					'type'    => 'select',
					'choices' => [
						'file'   => 'Upload file contents',
						'delete' => 'Delete file',
						'deleteDirectory' => 'Delete directory',
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
			]
		);
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	public function getConnection( array $config )
	{
		$host = $this->getRequestUrl( $config );
		$ftp = ftp_connect( $host, $config['port'] ?? 21 ) or throw new \Exception( 'Cannot connect to ' . $host );
		$login = ftp_login( $ftp, $config['username'] ?? '', $config['password'] ?? '' );

		if ( ! $login ) {
			throw new \Exception( 'Cannot login to ' . $host );
		}

		ftp_pasv( $ftp, ! empty( $config['passive'] ) );

		return $ftp;
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$ftp = $this->getConnection( $config );

		switch ( $config['action'] ?? '' ) {
			case 'dir':
				$result = $this->getDirectory( $config, $ftp );
			break;
			case 'file':
				$result = $this->getFile( $config, $ftp );
			break;
		}

		if(isset($result)){
			return new Result( $result["files"], $result["response"]??null );
		}

		throw new \Exception( 'No retrieve action selected' );
	}

	public function getFile($config, $ftp)
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( 'No filename configured' );
		}

		$path    = $config['path'] ?? '.';
		$file    = $path . '/' . $config['filename'];
		$tmpFile = $this->createTmpFile( $config['filename'] );
		$result = [];

		$success = $this->remoteToLocalFile($file, $tmpFile, $ftp);

		if ( ! $success ) {
			$message = 'Cannot fetch file from ' . $config['host'];
			if ( empty( $config['passive'] ) ) {
				$message .= '. ' . 'Please try passive mode.';
			}
			throw new \Exception( $message );
		} else {
			$result["response"] = $this->trans( "Sucesfully retrieved: " . $file );
		}

		// Get file path/name.
		$tmpFileName = $this->getResourcePath( $tmpFile );

		// Get file contents.
		$result["files"] = file_get_contents( $tmpFileName );

		try {
			if ( ! empty( $config['format'] ) ) {
				if ( $result["files"] ) {
					$result["files"] = $this->decodeFormat( $config['format'], $result["files"], $config );
				} else {
					// Try to decode from file.
					$result["files"] = $this->decodeFormat( $config['format'], $tmpFileName, $config );
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

	public function send( array $config, $data ): Result
	{
		switch ( $config['action'] ) {
			case 'file':
				$result = $this->sendFile( $config, $data );
			break;
			case 'delete':
				$result = $this->deleteFile( $config );
			break;
			case 'deleteDirectory':
				$result = $this->deleteDirectory( $config, $data );
			break;
		}
		if(isset($result))
		{
			return new Result( $data, $result["response"]??null );
		}

		throw new \Exception( 'No retrieve action selected' );
	}

	public function sendFile( array $config, $data ): mixed
	{
		$ftp = $this->getConnection( $config );

		$filecontent = $this->encodeFormat( $config['format'] ?? '', $data );
		$result['response'] = [];

		$filename = $config['filename'];
		if ( empty( $config['override'] ) ) {
			$directory = $this->getDirectory( $config, $ftp );
			$filename = $this->createUniqueFilename( $filename, $directory["files"] );
			array_push($result['response'], $directory["response"]." ". $this->trans("to create unique filename"));
		}

		$local_file = $this->createTmpFile();
		fwrite( $local_file, $filecontent );
		rewind( $local_file );

		$upload_result = $this->putFile($ftp, $config, $local_file, $filename);

		$this->removeTmpFile( $local_file );

		if ( ! $upload_result ) {
			throw new \Exception( 'Could not be write file to the server' );
		}else {
			array_push($result['response'],  $this->trans( "Sucesfully uploaded: " ) . $config['path'] . "/" . $filename);
		}

		return $result;
	}

	public function deleteFile($config)
	{
		$ftp = $this->getConnection( $config );

		$file = $config['path']."/".$config['filename'];
		$delete_result = $this->deleteSingleFile($ftp, $file);

		$result = [];

		if ( ! $delete_result ) {
			throw new \Exception( 'Could not delete file to the server' );
		}else {
			$result['response'] = $this->trans( "Sucesfully deleted: " ) . $file;
		}

		return $result;
	}

	public function deleteSingleFile($ftp, $file)
	{
		try {
			ftp_delete($ftp, $file);
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}

	public function deleteDirectory($ftp, $file)
	{
		try {
			ftp_rmdir($ftp, $file);
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}

	public function remoteToLocalFile($file, $tmpFile, $ftp)
	{
		return ftp_fget( $ftp, $tmpFile, $file );
	}

	public function putFile($ftp, $config, $local_file, $filename)
	{
		ftp_chdir( $ftp, $config['path'] );
		$upload_result = ftp_fput( $ftp, $filename, $local_file, FTP_BINARY );
		ftp_close( $ftp );
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

	public function getDirectory( $config, $ftp = null ): array
	{
		$result["files"] =  $this->listDirectory($ftp, $config);

		if ( ! is_array( $result["files"] ) ) {
			$message = 'Cannot read directory on ' . $config['host'];
			if ( empty( $config['passive'] ) ) {
				$message .= '. ' . 'Please try passive mode.';
			}
			throw new \Exception( $message );
		} else {
			$result["response"] = $this->trans( "Sucesfully retrieved: " . $config['path'] );
		}

		return $result;
	}

	public function listDirectory($ftp, $config)
	{
		return ftp_nlist( $ftp, $config['path'] ?? '.' );;
	}

	public function createUniqueFilename( $filename, $existing ): string
	{
		$ext           = pathinfo( $filename, PATHINFO_EXTENSION );
		$file_basename = basename( $filename, "." . $ext );

		$x = 1;
		while ( $x <= 999 ) {
			$newFilename = $file_basename . $x . "." . $ext;

			if ( ! in_array( $newFilename, $existing ) ) {
				$filename = $newFilename;
				break;
			}
			$x ++;
		}

		return $filename;
	}
}
