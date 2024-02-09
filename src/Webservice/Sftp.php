<?php

namespace SyncEngine\Webservice;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Net\SFTP as seclibSFTP;
use SyncEngine\Webservice\Helper\Result;

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

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	public function send( array $config, $data ): Result
	{
		$sftp = $this->getClientLoggedIn( $config );

		$filecontent = $this->encodeFormat( $config['format'] ?? '', $data );

		$filename = $config['filename'];
		if ( empty( $config['override'] ) ) {
			$directory = $this->getSftpDirectory( $config, $sftp );
			$filename = $this->createUniqueFilename( $filename, $directory["directory_files"] );
			$response[] = $directory["response"]." ". $this->trans("to create unique filename");
		}


		// Create tmp file for stream.
		$local_file = $this->createTmpFile();
		fwrite( $local_file, $filecontent );
		rewind( $local_file );

		// Stream file to FTP.
		$upload_result = $sftp->put( $config['path'] . "/" . $filename, $local_file, FTP_BINARY );

		$this->removeTmpFile( $local_file );

		if ( ! $upload_result ) {
			throw new \Exception( 'Could not be write file to the SFTP server' );
		} else {
			$response[] = $this->trans( "Sucesfully uploaded: " ) . $config['path'] . "/" . $filename;
		}

		return new Result( $data, $response ?? null );
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$sftp = $this->getClientLoggedIn( $config );

		if ( ! $sftp ) {
			throw new \Exception( 'Could not authenticate to the SFTP server' );
		}

		switch ( $config['fetch'] ?? '' ) {
			case 'dir':
				$directory = $this->getSftpDirectory( $config, $sftp );
				return new Result( $directory["directory_files"], $directory["response"]??null );;
			case 'file':
				return $this->getSftpFile( $config, $sftp );
		}

		throw new \Exception( 'No get fetch method selected' );
	}

	public function getSftpFile( $config, $sftp )
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( 'No filename configured' );
		}

		$path    = $config['path'] ?? '.';
		$file    = $path . '/' . $config['filename'];
		$tmpFile = $this->createTmpFile( $config['filename'] );

		$success = $sftp->get( $file, $tmpFile );

		if ( ! $success ) {
			$message = 'Cannot fetch file from ' . $config['host'];
			if ( empty( $config['passive'] ) ) {
				$message .= '. ' . 'Please try passive mode.';
			}
			throw new \Exception( $message );
		} else {
			$response[] = $this->trans( "Sucesfully retrieved: " . $file );
		}

		// Get file path/name.
		$tmpFileName = $this->getResourcePath( $tmpFile );

		// Get file contents.
		$content = file_get_contents( $tmpFileName );

		try {
			if ( ! empty( $config['format'] ) ) {
				if ( $content ) {
					$content = $this->decodeFormat( $config['format'], $content, $config );
				} else {
					// Try to decode from file.
					$content = $this->decodeFormat( $config['format'], $tmpFileName, $config );
				}
			}
		} catch ( \Throwable $e ) {
			$this->removeTmpFile( $tmpFileName );
			fclose( $tmpFile );
			throw $e;
		}

		$this->removeTmpFile( $tmpFileName );
		fclose( $tmpFile );

		return new Result( $content, $response ?? null );
	}

	public function getClient( array $config ): seclibSFTP
	{
		return new seclibSFTP( $config['host'], $config['port'] );
	}

	public function getPass( array $config ): string|PrivateKey
	{
		if ( $config['auth_method'] == "private_key" ) {
			$keyPass = ! empty( $config['keypassword'] ) ? $config['keypassword'] : null;

			return PublicKeyLoader::load( $config['key'], $keyPass );
		} else {
			return $config['password'];
		}
	}

	public function getClientLoggedIn( array $config ): ?seclibSFTP
	{
		$sftp  = $this->getClient( $config );
		$pw    = $this->getPass( $config );
		$login = $sftp->login( $config['username'], $pw );

		if ( $login ) {
			return $sftp;
		}

		return null;
	}

	public function getSftpDirectory( $config, $sftp = null ): array
	{
		$result["directory_files"] = $sftp->nlist( $config['path'] ?? '.' );

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
}
