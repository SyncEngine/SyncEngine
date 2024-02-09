<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
use Symfony\Component\Finder\Finder;

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
				'fetch'    => [
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
						'fetch'  => 'file',
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
						'fetch' => 'file',
					],*/
				],
			]
		);
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	public function getFtpConnection( array $config )
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
		if($config["_class"] === "Sftp"){
			$ftp = $this->getClientLoggedIn( $config );
		}else{
			$ftp = $this->getFtpConnection( $config );
		}

		switch ( $config['fetch'] ?? '' ) {
			case 'dir':
				$directory = $this->getDirectory( $config, $ftp );
				return new Result( $directory["directory_files"], $directory["response"]??null );;
			case 'file':
				$file = $this->getFile( $config, $ftp );
				return new Result( $file["file"], $file["response"] ?? null );
		}

		throw new \Exception( 'No get fetch method selected' );
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

		if($config["_class"] === "Sftp")
		{
			$success = $ftp->get( $file, $tmpFile );
		}else
		{
			$success = ftp_fget( $ftp, $tmpFile, $file );
		}

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
		$result["file"] = file_get_contents( $tmpFileName );

		try {
			if ( ! empty( $config['format'] ) ) {
				if ( $result["file"] ) {
					$result["file"] = $this->decodeFormat( $config['format'], $result["file"], $config );
				} else {
					// Try to decode from file.
					$result["file"] = $this->decodeFormat( $config['format'], $tmpFileName, $config );
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
		if($config["_class"] === "Sftp"){
			$ftp = $this->getClientLoggedIn( $config );
		}else{
			$ftp = $this->getFtpConnection( $config );
		}


		$filecontent = $this->encodeFormat( $config['format'] ?? '', $data );

		$filename = $config['filename'];
		if ( empty( $config['override'] ) ) {
			$directory = $this->getDirectory( $config, $ftp );
			$filename = $this->createUniqueFilename( $filename, $directory["directory_files"] );
			$response[] = $directory["response"]." ". $this->trans("to create unique filename");
		}

		$local_file = $this->createTmpFile();
		fwrite( $local_file, $filecontent );
		rewind( $local_file );

		if($config['_class'] === "Sftp"){
			$upload_result = $ftp->put( $config['path'] . "/" . $filename, $local_file, FTP_BINARY );
		}else {
			ftp_chdir( $ftp, $config['path'] );
			$upload_result = ftp_fput( $ftp, $filename, $local_file, FTP_BINARY );
			ftp_close( $ftp );
		}

		$this->removeTmpFile( $local_file );

		if ( ! $upload_result ) {
			throw new \Exception( 'Could not be write file to the server' );
		}else {
			$response[] = $this->trans( "Sucesfully uploaded: " ) . $config['path'] . "/" . $filename;
		}

		return new Result( $data, $response ?? null );
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
		if($config["_class"] === "Sftp")
		{
			$result["directory_files"] = $ftp->nlist( $config['path'] ?? '.' );
		}else{
			$result["directory_files"] = ftp_nlist( $ftp, $config['path'] ?? '.' );
		}

		if ( ! is_array( $result["directory_files"] ) ) {
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
