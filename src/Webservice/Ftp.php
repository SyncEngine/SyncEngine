<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use Symfony\Component\Finder\Finder;

class Ftp extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'ftp';
		$this->name        = 'FTP';
		$this->description = 'Connect to an FTP server to upload and/or download files';
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => 'Host',
				'type'  => 'text',
			],
			'username' => [
				'label' => 'Username',
				'type'  => 'text',
			],
			'password' => [
				'label' => 'Password',
				'type'  => 'password',
			],
			'port'     => [
				'label'   => 'Port',
				'type'    => 'number',
				'default' => 21,
			],
			'passive' => [
				'label' => 'Passive',
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
			'path'     => [
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
				'fetch'      => [
					'label'   => 'Select what you want to retrieve',
					'type'    => 'select',
					'choices' => [
						'file' => 'File contents',
						'dir'  => 'Directory filenames',
					],
				],
				'filename' => [
					'label' => 'Filename',
					'type'  => 'text',
					'conditional' => [
						'fetch' => 'file',
					],
					'fields' => [
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
					'label' => 'Filename',
					'type'  => 'text',
					/*'conditional' => [
						'method' => 'file',
					],*/
					'fields' => [
						'format' => $this->getFormatEncodeField(),
						'override' => [
							'label'   => 'Overwrite if file exists',
							'type'    => 'boolean',
						],
					],
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

	public function retrieve( array $config )
	{
		// Test connection first.
		$ftp = $this->getFtpConnection( $config );

		switch ( $config['method'] ?? '' ) {
			case 'dir':
				return $this->getFtpDirectory( $config, $ftp );

			case 'file':
				// @todo use Filesystem component?

				$path = $config['path'] ?? '.';

				if ( empty( $config['filename'] ) ) {
					throw new \Exception( 'No filename configured' );
				}

				$file = $path . '/' . $config['filename'];

				// Store file.
				$tmpFile = $this->createTmpFile( $config['filename'] );
				$success = ftp_fget( $ftp, $tmpFile, $file );

				if ( ! $success ) {
					$message = 'Cannot fetch file from ' . $config['host'];
					if ( empty( $config['passive'] ) ) {
						$message .= '. ' . 'Please try passive mode.';
					}
					throw new \Exception( $message );
				}

				// Get file contents.
				$fstats  = fstat( $tmpFile );
				$content = fread( $tmpFile, $fstats['size'] );

				// Get file path/name.
				$tmpFileName = $this->getResourcePath( $tmpFile );

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

				return $content;
		}

		throw new \Exception( 'No get method selected' );
	}

	public function send( array $config, $data )
	{
		$ftp = $this->getFtpConnection( $config );

		$filecontent = $this->encodeFormat( $config['format'] ?? '', $data );

		$filename = $config['filename'];
		if ( empty( $config['override'] ) ) {
			$filename = $this->createUniqueFilename( $filename, $this->getFtpDirectory( $config, $ftp ) );
		}

		// Create tmp file for stream.
		$local_file = $this->createTmpFile();
		fwrite( $local_file, $filecontent );
		rewind( $local_file );

		// Go to directory.
		ftp_chdir( $ftp, $config['path'] );

		// Stream file to FTP.
		$upload_result = ftp_fput( $ftp, $filename, $local_file, FTP_BINARY );

		ftp_close( $ftp );

		$this->removeTmpFile( $local_file );

		if ( ! $upload_result ) {
			throw new \Exception( 'Could not be write file to the FTP server' );
		}

		return $data;
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

	public function getFtpDirectory( $config, $ftp = null ): array
	{
		if ( ! $ftp ) {
			$ftp = $this->getFtpConnection( $config );
		}

		$directory_files = ftp_nlist( $ftp, $config['path'] ?? '.' );

		if ( ! is_array( $directory_files ) ) {
			$message = 'Cannot read directory on ' . $config['host'];
			if ( empty( $config['passive'] ) ) {
				$message .= '. ' . 'Please try passive mode.';
			}
			throw new \Exception( $message );
		}

		return $directory_files;
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
